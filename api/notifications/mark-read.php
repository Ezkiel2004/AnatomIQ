<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../notification-data.php';
requireMethod('POST');
$user = requireLogin(); $db = Database::getInstance(); $body = getJsonBody();
$visible = notificationData($user, $db);
$keys = array_column($visible, 'id');
$requested = !empty($body['all']) ? $keys : [(string)($body['id'] ?? '')];
foreach ($requested as $key) if (!in_array($key,$keys,true)) jsonError('Notification unavailable.',404);
$pdo = $db->getConnection(); $pdo->beginTransaction();
try {
    foreach ($requested as $key) {
        $db->query('INSERT IGNORE INTO notification_receipts (user_id, notification_key) VALUES (?, ?)', [$user['user_id'],$key]);
        if (str_starts_with($key,'announcement:')) $db->query('INSERT IGNORE INTO announcement_reads (announcement_id,user_id) VALUES (?,?)',[(int)substr($key,13),$user['user_id']]);
    }
    $pdo->commit();jsonSuccess(null,'Notifications marked as read.');
} catch (Throwable $error) { $pdo->rollBack();throw $error; }
