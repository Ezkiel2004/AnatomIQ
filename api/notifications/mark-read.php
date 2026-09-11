<?php
/**
 * AnatomIQ – Mark Notifications Read
 * POST /api/notifications/mark-read.php
 *
 * Body: { "id": 123 } — marks a specific announcement as read
 *       { "all": true } — marks all as read for the current user
 */

require_once __DIR__ . '/../helpers.php';
requireMethod('POST');

$user = requireLogin();
$body = getJsonBody();
$db   = Database::getInstance();

if (!empty($body['all'])) {
    // Mark ALL announcements as read for this user
    $announcements = $db->fetchAll("SELECT announcement_id FROM announcements WHERE is_published = 1");
    foreach ($announcements as $ann) {
        $db->query(
            "INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)",
            [$ann['announcement_id'], $user['user_id']]
        );
    }
    $_SESSION['read_virtual_notifs_all'] = time();
    $_SESSION['read_virtual_notifs'] = [];
    jsonSuccess(null, 'All notifications marked as read.');
}

$id = (int) ($body['id'] ?? 0);
if ($id > 0 && $id < 10000) {
    // Announcement read receipt
    $db->query(
        "INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)",
        [$id, $user['user_id']]
    );
    jsonSuccess(null, 'Notification marked as read.');
}

if ($id >= 10000) {
    if (!isset($_SESSION['read_virtual_notifs'])) {
        $_SESSION['read_virtual_notifs'] = [];
    }
    if (!in_array($id, $_SESSION['read_virtual_notifs'], true)) {
        $_SESSION['read_virtual_notifs'][] = $id;
    }
    jsonSuccess(null, 'Notification marked as read.');
}

jsonSuccess(null, 'OK.');
?>
