<?php
require_once __DIR__ . '/helpers.php';
requireTeacher();
requireMethod('GET', 'POST', 'DELETE');
$db = Database::getInstance();
if ($_SERVER['REQUEST_METHOD'] === 'GET') jsonSuccess($db->fetchAll('SELECT * FROM achievement_rules ORDER BY achievement_id'));
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $db->query('DELETE FROM achievement_rules WHERE achievement_id=?', [getIdParam()]);
    jsonSuccess(null, 'Goal removed.');
}
$body = getJsonBody();
$title = requireField($body, 'title');
$metric = requireField($body, 'metric');
$threshold = filter_var($body['threshold'] ?? null, FILTER_VALIDATE_FLOAT);
if (!in_array($metric, ['completed_lessons','avg_quiz_score','quizzes_taken','quizzes_passed','systems_explored','total_time_hours','overall_progress'], true) || $threshold === false || $threshold <= 0) jsonError('Choose a metric and a positive target.', 422);
$id = $db->insert('achievement_rules', ['title'=>$title,'metric'=>$metric,'threshold'=>$threshold]);
jsonSuccess(['achievement_id'=>$id], 'Goal created.', 201);
