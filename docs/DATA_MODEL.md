# Data Model (Minimum)
Fokus skema database (MySQL) pada MVP ini meliputi struktur inti agar status, HM/KM, dan WO terintegrasi secara *single source of truth*.

## 1. Entitas Pengguna & Hak Akses
- **users**: `user_id`, `name`, `email`, `password_hash`, `role_id`, `location_id` (opsional jika spesifik), `status`.
- **roles**: `role_id`, `role_name`, `permissions` (JSON/mapping aksi).

## 2. Entitas Master Asset & Lokasi
- **locations**: `location_id`, `name`, `type` (site, yard, pit).
- **assets**: `asset_id`, `code` (lambung), `plate`, `serial_number`, `make`, `model`, `category` (Dump Truck, Excavator, dll), `year`, `ownership`, `location_id`, `current_status`.

## 3. Entitas Operasional (HM/KM & Status)
- **meter_readings**: `id`, `asset_id`, `hm_reading`, `km_reading`, `reading_at`, `source`, `user_id`, `photo_url`, `is_valid`.
- **asset_status_logs**: `id`, `asset_id`, `status` (READY, BREAKDOWN, dll), `reason`, `changed_at`, `user_id`, `linked_wo_id`.

## 4. Entitas Work Order (WO)
- **work_orders**: `wo_id`, `wo_number`, `asset_id`, `type` (BREAKDOWN, PM, dll), `priority`, `complaint`, `diagnosis`, `cause`, `action`, `status` (DRAFT, IN_PROGRESS, CLOSED, dll), `created_at`, `closed_at`, `total_downtime_hours`, `location_id`.
- **wo_assignments**: `id`, `wo_id`, `mechanic_id`, `assigned_at`, `assigned_by`.
- **wo_time_logs**: `id`, `wo_id`, `mechanic_id`, `action_type` (START, PAUSE, RESUME, END), `action_time`, `reason` (jika pause/waiting part).

## 5. Entitas Pendukung (Attachment & Audit)
- **attachments**: `id`, `reference_type` (WO, ASSET, dll), `reference_id`, `file_type` (before, after, test), `file_path`, `uploaded_at`, `uploaded_by`.
- **audit_logs**: `id`, `entity_table`, `entity_id`, `action` (INSERT, UPDATE, DELETE), `old_value` (JSON), `new_value` (JSON), `user_id`, `timestamp`.
- **notifications**: `id`, `user_id`, `title`, `message`, `is_read`, `created_at`, `action_link`.
