<?php
require_once 'db.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

function reportJson($value) {
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function reportReply($status, $data = null, $message = null, $httpCode = 200) {
    http_response_code($httpCode);
    $payload = ['status' => $status];
    if ($data !== null) $payload['data'] = $data;
    if ($message !== null) $payload['message'] = $message;
    echo reportJson($payload);
    exit;
}

function reportDecodeJson($value, $fallback = []) {
    if ($value === null || $value === '') return $fallback;
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function ensureReportTables($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS report_templates (
        template_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        template_key VARCHAR(100) NOT NULL,
        code VARCHAR(80) NOT NULL,
        title VARCHAR(190) NOT NULL,
        version INT UNSIGNED NOT NULL DEFAULT 1,
        schema_json JSON NOT NULL,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_report_template_version (template_key, version),
        KEY idx_report_template_active (template_key, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS report_records (
        report_id CHAR(36) PRIMARY KEY,
        template_id BIGINT UNSIGNED NOT NULL,
        client_key VARCHAR(64) NOT NULL,
        report_number VARCHAR(190) NULL,
        status ENUM('DRAFT', 'FINAL', 'VOID') NOT NULL DEFAULT 'DRAFT',
        source_method VARCHAR(40) NOT NULL DEFAULT 'manual',
        field_data JSON NOT NULL,
        draft_data JSON NULL,
        standardized_payload JSON NULL,
        cloned_from_report_id CHAR(36) NULL,
        has_pending_attachments BOOLEAN NOT NULL DEFAULT FALSE,
        created_by INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        finalized_at TIMESTAMP NULL,
        voided_at TIMESTAMP NULL,
        final_number_key VARCHAR(320) NULL,
        UNIQUE KEY uq_report_final_number (final_number_key),
        KEY idx_report_status_updated (status, updated_at),
        KEY idx_report_client_draft (client_key, status, template_id),
        CONSTRAINT fk_report_template FOREIGN KEY (template_id) REFERENCES report_templates (template_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS report_items (
        item_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        report_id CHAR(36) NOT NULL,
        position INT UNSIGNED NOT NULL,
        item_data JSON NOT NULL,
        UNIQUE KEY uq_report_item_position (report_id, position),
        CONSTRAINT fk_report_item_record FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS report_audit_logs (
        audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        report_id CHAR(36) NOT NULL,
        client_key VARCHAR(64) NOT NULL,
        actor_id INT NULL,
        action VARCHAR(40) NOT NULL,
        payload_json JSON NULL,
        occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_report_audit_record (report_id, occurred_at),
        CONSTRAINT fk_report_audit_record FOREIGN KEY (report_id) REFERENCES report_records (report_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function reportUuid() {
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
}

function validClientKey($value) {
    return is_string($value) && preg_match('/^[A-Za-z0-9-]{8,64}$/', $value);
}

function valueIsEmpty($value) {
    if ($value === null) return true;
    if (is_string($value)) return trim($value) === '';
    if (is_array($value)) return count($value) === 0;
    return false;
}

function validateFinalReport($schema, $fields, $rows) {
    foreach (($schema['fields'] ?? []) as $definition) {
        if (!empty($definition['required'])) {
            $key = $definition['key'] ?? '';
            if ($key === '' || !array_key_exists($key, $fields) || valueIsEmpty($fields[$key])) {
                return 'Field wajib belum lengkap: ' . ($definition['label'] ?? $key);
            }
        }
    }

    $hasContent = false;
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        foreach ($row as $key => $value) {
            if (strpos((string)$key, '_') === 0) continue;
            if (!valueIsEmpty($value)) {
                $hasContent = true;
                break 2;
            }
        }
    }
    return $hasContent ? null : 'Minimal satu baris isi diperlukan sebelum laporan difinalkan.';
}

function upsertReportTemplate($db, $template) {
    $key = trim((string)($template['id'] ?? ''));
    $code = trim((string)($template['code'] ?? ''));
    $title = trim((string)($template['title'] ?? ''));
    $version = max(1, (int)($template['version'] ?? 1));
    $schema = $template['schema'] ?? [];
    if ($key === '' || $code === '' || $title === '' || !is_array($schema)) {
        throw new InvalidArgumentException('Definisi template laporan tidak lengkap.');
    }

    $stmt = $db->prepare("INSERT INTO report_templates
        (template_key, code, title, version, schema_json, is_active)
        VALUES (:template_key, :code, :title, :version, :schema_json, 1)
        ON DUPLICATE KEY UPDATE code = VALUES(code), title = VALUES(title), schema_json = VALUES(schema_json), is_active = 1");
    $stmt->execute([
        ':template_key' => $key,
        ':code' => $code,
        ':title' => $title,
        ':version' => $version,
        ':schema_json' => reportJson($schema)
    ]);
    $lookup = $db->prepare("SELECT template_id FROM report_templates WHERE template_key = :template_key AND version = :version LIMIT 1");
    $lookup->execute([':template_key' => $key, ':version' => $version]);
    return (int)$lookup->fetchColumn();
}

function replaceReportItems($db, $reportId, $rows) {
    $delete = $db->prepare("DELETE FROM report_items WHERE report_id = :report_id");
    $delete->execute([':report_id' => $reportId]);
    $insert = $db->prepare("INSERT INTO report_items (report_id, position, item_data) VALUES (:report_id, :position, :item_data)");
    foreach (array_values($rows) as $position => $row) {
        if (!is_array($row)) continue;
        $insert->execute([
            ':report_id' => $reportId,
            ':position' => $position,
            ':item_data' => reportJson($row)
        ]);
    }
}

function writeReportAudit($db, $reportId, $clientKey, $action, $payload = null) {
    $stmt = $db->prepare("INSERT INTO report_audit_logs (report_id, client_key, actor_id, action, payload_json)
        VALUES (:report_id, :client_key, :actor_id, :action, :payload_json)");
    $stmt->execute([
        ':report_id' => $reportId,
        ':client_key' => $clientKey,
        ':actor_id' => (int) api_current_user()['id'],
        ':action' => $action,
        ':payload_json' => $payload === null ? null : reportJson($payload)
    ]);
}

function getReportById($db, $reportId) {
    $stmt = $db->prepare("SELECT r.*, t.template_key, t.code, t.title, t.version, t.schema_json
        FROM report_records r JOIN report_templates t ON t.template_id = r.template_id
        WHERE r.report_id = :report_id LIMIT 1");
    $stmt->execute([':report_id' => $reportId]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $itemStmt = $db->prepare("SELECT item_data FROM report_items WHERE report_id = :report_id ORDER BY position ASC");
    $itemStmt->execute([':report_id' => $reportId]);
    $items = [];
    foreach ($itemStmt->fetchAll() as $item) $items[] = reportDecodeJson($item['item_data']);

    $fields = reportDecodeJson($row['field_data']);
    $draftMeta = reportDecodeJson($row['draft_data']);
    $draft = array_merge($draftMeta, ['fields' => $fields, 'rows' => $items]);
    $standardized = reportDecodeJson($row['standardized_payload'], null);
    if ($standardized === null && $row['status'] === 'FINAL') {
        $standardized = ['schemaId' => $row['template_key'], 'schemaVersion' => 'report-template-v2', 'fields' => $fields, 'rows' => $items];
    }

    return [
        'id' => $row['report_id'],
        'schemaVersion' => 'report-template-v2',
        'sourceMethod' => $row['source_method'],
        'schemaId' => $row['template_key'],
        'code' => $row['code'],
        'title' => $row['title'],
        'reportNumber' => $row['report_number'],
        'status' => $row['status'],
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
        'finalizedAt' => $row['finalized_at'],
        'voidedAt' => $row['voided_at'],
        'draft' => $draft,
        'standardizedPayload' => $standardized,
        'hasPendingAttachments' => (bool)$row['has_pending_attachments'],
        'backend' => true
    ];
}

try {
    ensureReportTables($db);

    if ($method === 'GET') {
        $id = trim((string)($_GET['id'] ?? ''));
        if ($id !== '') {
            $record = getReportById($db, $id);
            if (!$record) reportReply('error', null, 'Laporan tidak ditemukan.', 404);
            reportReply('success', $record);
        }

        $action = strtolower(trim((string)($_GET['action'] ?? '')));
        if ($action === 'draft') {
            $schemaId = trim((string)($_GET['schema_id'] ?? ''));
            $clientKey = trim((string)($_GET['client_key'] ?? ''));
            if ($schemaId === '' || !validClientKey($clientKey)) reportReply('error', null, 'Parameter draft tidak valid.', 422);
            $stmt = $db->prepare("SELECT r.report_id FROM report_records r
                JOIN report_templates t ON t.template_id = r.template_id
                WHERE r.status = 'DRAFT' AND r.client_key = :client_key AND t.template_key = :schema_id
                ORDER BY r.updated_at DESC LIMIT 1");
            $stmt->execute([':client_key' => $clientKey, ':schema_id' => $schemaId]);
            $draftId = $stmt->fetchColumn();
            reportReply('success', $draftId ? getReportById($db, $draftId) : null);
        }

        $status = strtoupper(trim((string)($_GET['status'] ?? 'FINAL')));
        if (!in_array($status, ['DRAFT', 'FINAL', 'VOID'], true)) reportReply('error', null, 'Status laporan tidak valid.', 422);
        $limit = min(200, max(1, (int)($_GET['limit'] ?? 100)));
        $schemaId = trim((string)($_GET['schema_id'] ?? ''));
        $sql = "SELECT r.report_id FROM report_records r JOIN report_templates t ON t.template_id = r.template_id WHERE r.status = :status";
        $params = [':status' => $status];
        if ($schemaId !== '') {
            $sql .= " AND t.template_key = :schema_id";
            $params[':schema_id'] = $schemaId;
        }
        $sql .= " ORDER BY COALESCE(r.finalized_at, r.updated_at) DESC LIMIT " . $limit;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = [];
        foreach ($stmt->fetchAll() as $row) {
            $record = getReportById($db, $row['report_id']);
            if ($record) $records[] = $record;
        }
        reportReply('success', $records);
    }

    if ($method !== 'POST') reportReply('error', null, 'Metode tidak diizinkan.', 405);
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) reportReply('error', null, 'Payload JSON tidak valid.', 400);
    $action = strtolower(trim((string)($input['action'] ?? '')));
    $clientKey = trim((string)($input['clientKey'] ?? ''));
    if (!validClientKey($clientKey)) reportReply('error', null, 'Identitas browser tidak valid.', 422);

    if ($action === 'save_draft' || $action === 'finalize') {
        $template = $input['template'] ?? [];
        $draft = $input['draft'] ?? [];
        $fields = $draft['fields'] ?? [];
        $rows = $draft['rows'] ?? [];
        if (!is_array($template) || !is_array($draft) || !is_array($fields) || !is_array($rows)) {
            reportReply('error', null, 'Struktur laporan tidak valid.', 422);
        }
        $reportId = trim((string)($input['reportId'] ?? ''));
        if ($reportId === '') $reportId = reportUuid();
        $reportNumber = trim((string)($input['reportNumber'] ?? ($fields['nomor'] ?? ($fields['no_dokumen'] ?? ''))));
        $sourceMethod = substr(trim((string)($input['sourceMethod'] ?? 'manual')), 0, 40);
        $hasPendingAttachments = !empty($input['hasPendingAttachments']) ? 1 : 0;

        if ($action === 'finalize') {
            if ($reportNumber === '') reportReply('error', null, 'Nomor laporan wajib diisi sebelum finalisasi.', 422);
            $validationError = validateFinalReport($template['schema'] ?? [], $fields, $rows);
            if ($validationError !== null) reportReply('error', null, $validationError, 422);
        }

        $db->beginTransaction();
        try {
            $templateId = upsertReportTemplate($db, $template);
            $existing = $db->prepare("SELECT status, client_key FROM report_records WHERE report_id = :report_id FOR UPDATE");
            $existing->execute([':report_id' => $reportId]);
            $current = $existing->fetch();
            if ($current && $current['status'] !== 'DRAFT') throw new DomainException('Laporan yang sudah final atau void tidak dapat ditimpa.');
            if ($current && $current['client_key'] !== $clientKey) throw new DomainException('Draft ini dimiliki sesi browser lain.');

            $status = $action === 'finalize' ? 'FINAL' : 'DRAFT';
            $draftMeta = $draft;
            unset($draftMeta['fields'], $draftMeta['rows']);
            $standardized = $action === 'finalize' ? ($input['standardizedPayload'] ?? [
                'schemaId' => $template['id'] ?? '', 'schemaVersion' => 'report-template-v2', 'fields' => $fields, 'rows' => $rows
            ]) : null;
            $finalNumberKey = $status === 'FINAL' ? (($template['id'] ?? '') . '|' . strtolower($reportNumber)) : null;

            if ($current) {
                $stmt = $db->prepare("UPDATE report_records SET template_id = :template_id, report_number = :report_number,
                    status = :status, source_method = :source_method, field_data = :field_data, draft_data = :draft_data,
                    standardized_payload = :standardized_payload, has_pending_attachments = :has_pending_attachments,
                    finalized_at = :finalized_at, final_number_key = :final_number_key WHERE report_id = :report_id");
            } else {
                $stmt = $db->prepare("INSERT INTO report_records
                    (report_id, template_id, client_key, report_number, status, source_method, field_data, draft_data,
                     standardized_payload, has_pending_attachments, created_by, finalized_at, final_number_key)
                    VALUES (:report_id, :template_id, :client_key, :report_number, :status, :source_method, :field_data,
                     :draft_data, :standardized_payload, :has_pending_attachments, :created_by, :finalized_at, :final_number_key)");
            }
            $params = [
                ':report_id' => $reportId,
                ':template_id' => $templateId,
                ':report_number' => $reportNumber === '' ? null : $reportNumber,
                ':status' => $status,
                ':source_method' => $sourceMethod === '' ? 'manual' : $sourceMethod,
                ':field_data' => reportJson($fields),
                ':draft_data' => reportJson($draftMeta),
                ':standardized_payload' => $standardized === null ? null : reportJson($standardized),
                ':has_pending_attachments' => $hasPendingAttachments,
                ':finalized_at' => $status === 'FINAL' ? date('Y-m-d H:i:s') : null,
                ':final_number_key' => $finalNumberKey
            ];
            if (!$current) {
                $params[':client_key'] = $clientKey;
                $params[':created_by'] = (int) api_current_user()['id'];
            }
            $stmt->execute($params);
            replaceReportItems($db, $reportId, $rows);
            writeReportAudit($db, $reportId, $clientKey, strtoupper($action), ['reportNumber' => $reportNumber]);
            $db->commit();
            reportReply('success', getReportById($db, $reportId), $status === 'FINAL' ? 'Laporan berhasil difinalkan.' : 'Draft tersimpan di server.');
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            if ($e->getCode() === '23000') reportReply('error', null, 'Nomor laporan final sudah digunakan.', 409);
            error_log('Report persistence error: ' . $e->getMessage());
            reportReply('error', null, 'Laporan gagal disimpan ke database.', 500);
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            reportReply('error', null, $e->getMessage(), 409);
        }
    }

    if ($action === 'discard_draft') {
        $reportId = trim((string)($input['reportId'] ?? ''));
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE report_records SET status = 'VOID', voided_at = CURRENT_TIMESTAMP
            WHERE report_id = :report_id AND client_key = :client_key AND status = 'DRAFT'");
        $stmt->execute([':report_id' => $reportId, ':client_key' => $clientKey]);
        if ($stmt->rowCount() < 1) {
            $db->rollBack();
            reportReply('error', null, 'Draft tidak ditemukan atau bukan milik browser ini.', 404);
        }
        writeReportAudit($db, $reportId, $clientKey, 'DISCARD_DRAFT');
        $db->commit();
        reportReply('success', ['id' => $reportId], 'Draft server berhasil dikosongkan.');
    }

    if ($action === 'clone') {
        $sourceId = trim((string)($input['reportId'] ?? ''));
        $source = getReportById($db, $sourceId);
        if (!$source || $source['status'] !== 'FINAL') reportReply('error', null, 'Laporan sumber tidak ditemukan atau belum final.', 404);
        $templateStmt = $db->prepare("SELECT template_id FROM report_records WHERE report_id = :report_id");
        $templateStmt->execute([':report_id' => $sourceId]);
        $templateId = (int)$templateStmt->fetchColumn();
        $newId = reportUuid();
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO report_records
            (report_id, template_id, client_key, report_number, status, source_method, field_data, draft_data, cloned_from_report_id, has_pending_attachments, created_by)
            VALUES (:report_id, :template_id, :client_key, NULL, 'DRAFT', 'clone', :field_data, :draft_data, :cloned_from, :has_pending_attachments, :created_by)");
        $stmt->execute([
            ':report_id' => $newId, ':template_id' => $templateId, ':client_key' => $clientKey,
            ':field_data' => reportJson($source['draft']['fields'] ?? []),
            ':draft_data' => reportJson(['updatedAt' => date(DATE_ATOM)]),
            ':cloned_from' => $sourceId, ':has_pending_attachments' => !empty($source['hasPendingAttachments']) ? 1 : 0,
            ':created_by' => (int) api_current_user()['id']
        ]);
        replaceReportItems($db, $newId, $source['draft']['rows'] ?? []);
        writeReportAudit($db, $newId, $clientKey, 'CLONE', ['sourceReportId' => $sourceId]);
        $db->commit();
        reportReply('success', getReportById($db, $newId), 'Salinan draft berhasil dibuat.');
    }

    if ($action === 'void') {
        api_require_permission('reports.approve');
        $reportId = trim((string)($input['reportId'] ?? ''));
        $reason = trim((string)($input['reason'] ?? ''));
        $stmt = $db->prepare("UPDATE report_records SET status = 'VOID', voided_at = CURRENT_TIMESTAMP,
            final_number_key = NULL WHERE report_id = :report_id AND status = 'FINAL'");
        $stmt->execute([':report_id' => $reportId]);
        if ($stmt->rowCount() < 1) reportReply('error', null, 'Laporan final tidak ditemukan atau sudah void.', 404);
        writeReportAudit($db, $reportId, $clientKey, 'VOID', ['reason' => $reason]);
        reportReply('success', getReportById($db, $reportId), 'Laporan berhasil dibatalkan (void).');
    }

    reportReply('error', null, 'Aksi laporan tidak dikenal.', 422);
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Reports API error: ' . $e->getMessage());
    reportReply('error', null, 'Layanan laporan sedang tidak tersedia.', 500);
}
?>
