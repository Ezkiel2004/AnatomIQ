<?php
/**
 * AnatomIQ – Notifications API Endpoint
 * GET /api/notifications.php
 *
 * Returns recent notifications for the currently logged-in user,
 * sourced from the announcements table + activity events.
 *
 * Response:
 *   { "success": true, "data": [ { id, icon, bg, title, body, time, unread, type, action } ] }
 */

require_once __DIR__ . '/helpers.php';
requireMethod('GET');

$user = requireLogin();
$db   = Database::getInstance();

$notifications = [];

// ── Announcements as notifications ─────────────────────────────
$announcements = $db->fetchAll(
    "SELECT a.announcement_id AS id,
            a.title,
            a.body,
            a.category,
            a.created_at,
            (ar.read_id IS NULL) AS is_unread
     FROM announcements a
     LEFT JOIN announcement_reads ar
           ON ar.announcement_id = a.announcement_id
          AND ar.user_id = ?
     WHERE a.is_published = 1
       AND (a.audience = 'all'
            OR a.audience = (
                SELECT CONCAT('section_', LOWER(REPLACE(sp.section, 'Grade 10 – ', '')))
                FROM student_profiles sp
                WHERE sp.user_id = ?
                LIMIT 1
            )
       )
     ORDER BY a.is_pinned DESC, a.created_at DESC
     LIMIT 10",
    [$user['user_id'], $user['user_id']]
);

$catIcons = [
    'general'   => ['icon' => '📢', 'bg' => 'rgba(139,92,246,0.15)'],
    'lesson'    => ['icon' => '📚', 'bg' => 'rgba(59,130,246,0.15)'],
    'quiz'      => ['icon' => '📝', 'bg' => 'rgba(245,158,11,0.15)'],
    'urgent'    => ['icon' => '🚨', 'bg' => 'rgba(220,38,38,0.15)'],
    'event'     => ['icon' => '🎉', 'bg' => 'rgba(16,185,129,0.15)'],
];

foreach ($announcements as $ann) {
    $meta = $catIcons[$ann['category']] ?? $catIcons['general'];
    $notifications[] = [
        'id'     => (int) $ann['id'],
        'type'   => 'announcement',
        'icon'   => $meta['icon'],
        'bg'     => $meta['bg'],
        'title'  => $ann['title'],
        'body'   => mb_strimwidth($ann['body'], 0, 100, '…'),
        'time'   => _timeAgo($ann['created_at']),
        'unread' => (bool) $ann['is_unread'],
        'action' => 'notifications.html',
    ];
}

// ── Score notifications (recent graded submissions) ─────────────
$readVirtuals = $_SESSION['read_virtual_notifs'] ?? [];
$allReadAfter = (int)($_SESSION['read_virtual_notifs_all'] ?? 0);

if ($user['role'] === 'student') {
    $graded = $db->fetchAll(
        "SELECT sub.submission_id, sub.score, sub.graded_at, a.title AS assess_title
         FROM assessment_submissions sub
         JOIN assessments a ON a.assessment_id = sub.assessment_id
         WHERE sub.student_id = ?
           AND sub.status = 'graded'
           AND sub.graded_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
         ORDER BY sub.graded_at DESC
         LIMIT 5",
        [$user['user_id']]
    );
    foreach ($graded as $g) {
        $notifId = 10000 + (int) $g['submission_id'];
        $isUnread = ($allReadAfter === 0 || strtotime($g['graded_at']) > $allReadAfter) && !in_array($notifId, $readVirtuals, true);
        $notifications[] = [
            'id'     => $notifId,
            'type'   => 'score',
            'icon'   => '🏆',
            'bg'     => 'rgba(20,184,166,0.15)',
            'title'  => 'Score Posted: ' . $g['assess_title'],
            'body'   => 'Your quiz has been graded: ' . round($g['score'], 1) . '%',
            'time'   => _timeAgo($g['graded_at']),
            'unread' => $isUnread,
            'action' => 'scores.html',
        ];
    }

    // Upcoming due assessments
    $upcoming = $db->fetchAll(
        "SELECT a.assessment_id, a.title, a.due_date
         FROM assessments a
         WHERE a.status = 'active'
           AND a.due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
           AND a.assessment_id NOT IN (
               SELECT assessment_id FROM assessment_submissions WHERE student_id = ?
           )
         ORDER BY a.due_date ASC
         LIMIT 3",
        [$user['user_id']]
    );
    foreach ($upcoming as $u) {
        $notifId = 20000 + (int) $u['assessment_id'];
        $isUnread = ($allReadAfter === 0) && !in_array($notifId, $readVirtuals, true);
        $notifications[] = [
            'id'     => $notifId,
            'type'   => 'quiz',
            'icon'   => '⏰',
            'bg'     => 'rgba(245,158,11,0.15)',
            'title'  => 'Due Soon: ' . $u['title'],
            'body'   => 'Due ' . date('M j', strtotime($u['due_date'])),
            'time'   => _timeAgo($u['due_date'], true),
            'unread' => $isUnread,
            'action' => 'quiz.html',
        ];
    }
}

// Sort by unread first, then by ID desc (newest first)
usort($notifications, fn($a, $b) => ($b['unread'] <=> $a['unread']) ?: ($b['id'] <=> $a['id']));

jsonSuccess($notifications);

// ── Helper: time ago ─────────────────────────────────────────────
function _timeAgo(string $datetime, bool $future = false): string {
    $diff = time() - strtotime($datetime);
    if ($future) $diff = -$diff;
    if ($diff < 0)    return 'soon';
    if ($diff < 60)   return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
}
?>
