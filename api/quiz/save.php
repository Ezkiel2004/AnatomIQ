<?php
require_once __DIR__ . '/../helpers.php';
requireMethod('POST');
$user = requireStudent();
$body = getJsonBody();
$id = (int)requireField($body, 'submission_id');
$answers = $body['answers'] ?? [];
if (!is_array($answers) || count($answers) > 500) jsonError('Invalid answers.', 422);
$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->beginTransaction();
$attempt = $db->fetchOne("SELECT *, deadline_at IS NOT NULL AND deadline_at <= NOW() AS expired FROM assessment_submissions WHERE submission_id=? AND student_id=? FOR UPDATE", [$id, $user['user_id']]);
if (!$attempt || $attempt['status'] !== 'in_progress') jsonError('Attempt is no longer open.', 409);
if ($attempt['expired']) jsonError('Time has expired. Submit your saved answers.', 409);
$allowed = array_column($db->fetchAll('SELECT question_id FROM questions WHERE assessment_id=?', [$attempt['assessment_id']]), 'question_id');
foreach ($answers as $answer) if (!is_array($answer) || !in_array((int)($answer['question_id'] ?? 0), $allowed)) jsonError('Invalid question in answers.', 422);
$db->query('UPDATE assessment_submissions SET draft_answers=? WHERE submission_id=?', [json_encode(array_values($answers), JSON_UNESCAPED_UNICODE), $id]);
$pdo->commit();
jsonSuccess(null, 'Answers saved.');
