<?php
require_once __DIR__ . '/../helpers.php';
requireMethod('POST');
$user = requireStudent();
$db = Database::getInstance();
$body = getJsonBody();
$id = (int)requireField($body, 'assessment_id');
$pdo = $db->getConnection();
$pdo->beginTransaction();
// Serialize starts across sessions for the same student.
$db->fetchOne('SELECT user_id FROM users WHERE user_id=? FOR UPDATE', [$user['user_id']]);
$assessment = $db->fetchOne('SELECT *, due_date IS NOT NULL AND due_date < NOW() AS overdue FROM assessments WHERE assessment_id=?', [$id]);
if (!$assessment || $assessment['status'] !== 'active') jsonError('Assessment is not available.', 403);
$attempt = $db->fetchOne("SELECT * FROM assessment_submissions WHERE student_id=? AND assessment_id=? AND status='in_progress' ORDER BY submission_id DESC LIMIT 1", [$user['user_id'], $id]);
$resumed = (bool)$attempt;
if (!$attempt) {
    if ($assessment['overdue']) jsonError('The assessment deadline has passed.', 403);
    $count = (int)$db->fetchOne("SELECT COUNT(*) AS n FROM assessment_submissions WHERE student_id=? AND assessment_id=? AND status IN ('submitted','graded')", [$user['user_id'], $id])['n'];
    if ($count >= (int)$assessment['max_attempts']) jsonError('Maximum attempts reached.', 403);
    if (!$db->fetchOne('SELECT question_id FROM questions WHERE assessment_id=? LIMIT 1', [$id])) jsonError('Your teacher has not added questions yet.', 409);
    $deadline = $db->fetchOne("SELECT CASE WHEN ? IS NULL THEN ? WHEN ? IS NULL THEN DATE_ADD(NOW(), INTERVAL ? MINUTE) ELSE LEAST(?, DATE_ADD(NOW(), INTERVAL ? MINUTE)) END AS value", [$assessment['time_limit_mins'], $assessment['due_date'], $assessment['due_date'], $assessment['time_limit_mins'], $assessment['due_date'], $assessment['time_limit_mins']])['value'];
    $newId = $db->insert('assessment_submissions', ['student_id'=>$user['user_id'],'assessment_id'=>$id,'attempt_number'=>$count+1,'status'=>'in_progress','started_at'=>date('Y-m-d H:i:s'),'deadline_at'=>$deadline,'draft_answers'=>'[]']);
    $db->query('UPDATE assessment_submissions SET started_at=NOW() WHERE submission_id=?', [$newId]);
    $attempt = $db->fetchOne('SELECT * FROM assessment_submissions WHERE submission_id=?', [$newId]);
}
$timing = $db->fetchOne('SELECT TIMESTAMPDIFF(SECOND, NOW(), deadline_at) AS remaining_seconds, TIMESTAMPDIFF(SECOND, started_at, NOW()) AS elapsed_seconds FROM assessment_submissions WHERE submission_id=?', [$attempt['submission_id']]);
$pdo->commit();
jsonSuccess(['submission_id'=>(int)$attempt['submission_id'],'attempt_number'=>(int)$attempt['attempt_number'],'time_limit_mins'=>$assessment['time_limit_mins'],'started_at'=>$attempt['started_at'],'remaining_seconds'=>$timing['remaining_seconds'] === null ? null : max(0,(int)$timing['remaining_seconds']),'elapsed_seconds'=>(int)$timing['elapsed_seconds'],'answers'=>json_decode($attempt['draft_answers'] ?? '[]', true) ?: [],'resumed'=>$resumed]);
