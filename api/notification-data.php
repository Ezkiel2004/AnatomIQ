<?php
function notificationData(array $user, Database $db): array {
    $read = array_column($db->fetchAll('SELECT notification_key FROM notification_receipts WHERE user_id=?', [$user['user_id']]), 'notification_key');
    $notifications = [];
    $scope = $user['role'] === 'student' ? "AND (a.audience='all' OR a.audience=?)" : '';
    $params = [$user['user_id']];
    if ($scope !== '') $params[] = studentAudience((int)$user['user_id']);
    $announcements = $db->fetchAll("SELECT a.*, ar.read_id FROM announcements a LEFT JOIN announcement_reads ar ON ar.announcement_id=a.announcement_id AND ar.user_id=? WHERE a.is_published=1 $scope ORDER BY a.is_pinned DESC, a.created_at DESC LIMIT 20", $params);
    foreach ($announcements as $ann) {
        $key = 'announcement:' . $ann['announcement_id'];
        $notifications[] = ['id'=>$key,'type'=>'announcement','title'=>$ann['title'],'body'=>mb_strimwidth($ann['body'],0,160,'…'),'time'=>$ann['created_at'],'event_at'=>$ann['created_at'],'unread'=>!$ann['read_id'] && !in_array($key,$read,true),'action'=>'notifications.html'];
    }
    if ($user['role'] === 'student') {
        $graded = $db->fetchAll("SELECT sub.submission_id, sub.score, sub.graded_at, a.title FROM assessment_submissions sub JOIN assessments a ON a.assessment_id=sub.assessment_id WHERE sub.student_id=? AND sub.status='graded' AND sub.graded_at>DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY sub.graded_at DESC LIMIT 10", [$user['user_id']]);
        foreach ($graded as $grade) {
            $key = 'score:' . $grade['submission_id'];
            $notifications[] = ['id'=>$key,'type'=>'score','title'=>'Score posted: '.$grade['title'],'body'=>'Your quiz score is '.round((float)$grade['score'],1).'%.','time'=>$grade['graded_at'],'event_at'=>$grade['graded_at'],'unread'=>!in_array($key,$read,true),'action'=>'progress.html#scores'];
        }
        $upcoming = $db->fetchAll("SELECT a.assessment_id, a.title, a.due_date, a.created_at FROM assessments a WHERE a.status='active' AND a.due_date BETWEEN NOW() AND DATE_ADD(NOW(),INTERVAL 3 DAY) AND NOT EXISTS (SELECT 1 FROM assessment_submissions sub WHERE sub.student_id=? AND sub.assessment_id=a.assessment_id AND sub.status IN ('submitted','graded')) ORDER BY a.due_date LIMIT 10", [$user['user_id']]);
        foreach ($upcoming as $assessment) {
            $key = 'assessment:' . $assessment['assessment_id'];
            $notifications[] = ['id'=>$key,'type'=>'quiz','title'=>'Due soon: '.$assessment['title'],'body'=>'Due '.$assessment['due_date'],'time'=>$assessment['due_date'],'event_at'=>$assessment['created_at'],'unread'=>!in_array($key,$read,true),'action'=>'quiz.html?assessment_id='.$assessment['assessment_id']];
        }
    }
    usort($notifications, fn($a,$b)=>($b['unread']<=>$a['unread']) ?: strcmp($b['event_at'],$a['event_at']));
    return $notifications;
}
