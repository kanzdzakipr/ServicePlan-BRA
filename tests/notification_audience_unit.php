<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/NotificationAudience.php';

$users = [
    ['user_id' => 1, 'is_active' => true, 'assigned_location_id' => 1, 'roles' => ['Administrator']],
    ['user_id' => 2, 'is_active' => true, 'assigned_location_id' => 1, 'roles' => ['Maintenance Planner']],
    ['user_id' => 3, 'is_active' => true, 'assigned_location_id' => 1, 'roles' => ['Logistic Head']],
    ['user_id' => 4, 'is_active' => true, 'assigned_location_id' => 2, 'roles' => ['Logistic Head']],
    ['user_id' => 5, 'is_active' => true, 'assigned_location_id' => 1, 'roles' => ['Mekanik Senior']],
    ['user_id' => 6, 'is_active' => true, 'assigned_location_id' => 1, 'roles' => ['HRD Manager']],
    ['user_id' => 7, 'is_active' => false, 'assigned_location_id' => 1, 'roles' => ['Logistic Head']],
    ['user_id' => 8, 'is_active' => true, 'assigned_location_id' => null, 'roles' => ['Equipment Manager']],
];

$actual = NotificationAudience::recipientIds($users, 1, 2);
sort($actual);
$expected = [1, 2, 3, 5, 8];
if ($actual !== $expected) {
    fwrite(STDERR, 'FAIL: audience lokasi/role tidak tepat: ' . json_encode($actual) . PHP_EOL);
    exit(1);
}

$unknownLocation = NotificationAudience::recipientIds($users, null, 2);
sort($unknownLocation);
if ($unknownLocation !== [1, 2, 8]) {
    fwrite(STDERR, 'FAIL: lokasi tanpa identitas bocor ke role lokal.' . PHP_EOL);
    exit(1);
}

echo "PASS: role, lokasi, akun nonaktif, dan actor terisolasi.\n";
