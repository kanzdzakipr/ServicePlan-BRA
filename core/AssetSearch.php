<?php
declare(strict_types=1);

final class AssetSearch
{
    private const COLUMNS = ['a.asset_id', 'a.asset_code', 'a.hull_number', 'a.alias_name', 'a.serial_number', 'a.license_plate'];
    public const MAX_RESULTS = 20;

    public static function validateQuery(string $query): string
    {
        $query = trim($query);
        if (mb_strlen($query, 'UTF-8') < 2 || mb_strlen($query, 'UTF-8') > 100) {
            throw new InvalidArgumentException('Masukkan 2–100 karakter untuk pencarian.');
        }
        return $query;
    }

    public static function escapeLike(string $query): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
    }

    public static function find(PDO $db, string $query, array $scope): array
    {
        $query = self::validateQuery($query);
        $escaped = self::escapeLike($query);
        $results = [];
        $seen = [];
        foreach ([$escaped . '%', '%' . $escaped . '%'] as $pattern) {
            $conditions = [];
            $params = $scope['params'];
            foreach (self::COLUMNS as $index => $column) {
                $key = ':search_' . $index;
                $conditions[] = "$column LIKE $key ESCAPE '!'";
                $params[$key] = $pattern;
            }
            $sql = 'SELECT a.asset_id, a.asset_code, a.hull_number, a.alias_name,
                           a.serial_number, a.license_plate, a.category, a.make_model,
                           a.status, a.current_location_id, l.location_name
                    FROM assets a
                    LEFT JOIN locations l ON l.location_id = a.current_location_id
                    WHERE (' . implode(' OR ', $conditions) . ')
                      AND NOT EXISTS (
                          SELECT 1 FROM archived_items arc 
                          WHERE arc.item_type = \'asset\' AND arc.item_id = a.asset_id
                      )';
            if ($scope['sql'] !== '') $sql .= ' AND (' . $scope['sql'] . ')';
            $sql .= ' ORDER BY a.asset_code, a.asset_id LIMIT ' . self::MAX_RESULTS;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (isset($seen[$row['asset_id']])) continue;
                $seen[$row['asset_id']] = true;
                $row['hull_number'] = $row['hull_number'] ?: ($row['alias_name'] ?: null);
                unset($row['alias_name']);
                $results[] = $row;
                if (count($results) >= self::MAX_RESULTS) break 2;
            }
        }
        return $results;
    }
}
