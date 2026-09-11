<?php
/**
 * AnatomIQ – Start Quiz API
 * POST /api/quiz/start.php
 *
 * Initiates an assessment attempt for a student.
 * Validates active status and attempt limits.
 *
 * Request: { "assessment_id": 1 }
 * Response: { "submission_id": 12, "attempt_number": 1, "time_limit_mins": 30 }
 */

require_once __DIR__ . '/../helpers.php';
requireMethod('POST');
$user = requireStudent();

$db   = Database::getInstance();
$body = getJsonBody();

$assessmentId = (int) requireField($body, 'assessment_id', 'Assessment ID');

// Fetch assessment
$assessment = $db->fetchOne(
    "SELECT * FROM assessments WHERE assessment_id = ?",
    [$assessmentId]
);

if (!$assessment) {
    jsonError('Assessment not found.', 404);
}

if ($assessment['status'] !== 'active') {
    jsonError('This assessment is not currently active.', 403);
}

// Count prior completed attempts
$pastAttempts = $db->fetchAll(
    "SELECT submission_id FROM assessment_submissions
     WHERE student_id = ? AND assessment_id = ? AND status IN ('submitted', 'graded')",
    [$user['user_id'], $assessmentId]
);

$attemptCount = count($pastAttempts);
if ($attemptCount >= (int) $assessment['max_attempts']) {
    jsonError('Maximum attempt limit (' . $assessment['max_attempts'] . ') reached for this assessment.', 403);
}

// Check if an attempt is already in progress
$existingInProgress = $db->fetchOne(
    "SELECT submission_id, attempt_number, started_at
     FROM assessment_submissions
     WHERE student_id = ? AND assessment_id = ? AND status = 'in_progress'
     ORDER BY started_at DESC LIMIT 1",
    [$user['user_id'], $assessmentId]
);

if ($existingInProgress) {
    jsonSuccess([
        'submission_id'   => (int) $existingInProgress['submission_id'],
        'attempt_number'  => (int) $existingInProgress['attempt_number'],
        'time_limit_mins' => $assessment['time_limit_mins'] ? (int) $assessment['time_limit_mins'] : null,
        'started_at'      => $existingInProgress['started_at'],
        'resumed'         => true,
    ], 'Resuming quiz session.');
}

// Create new submission record
$nextAttemptNumber = $attemptCount + 1;
$db->query(
    "INSERT INTO assessment_submissions
        (student_id, assessment_id, attempt_number, status, started_at)
     VALUES (?, ?, ?, 'in_progress', NOW())",
    [$user['user_id'], $assessmentId, $nextAttemptNumber]
);

$newSubmissionId = (int) $db->getConnection()->lastInsertId();

jsonSuccess([
    'submission_id'   => $newSubmissionId,
    'attempt_number'  => $nextAttemptNumber,
    'time_limit_mins' => $assessment['time_limit_mins'] ? (int) $assessment['time_limit_mins'] : null,
    'started_at'      => date('Y-m-d H:i:s'),
    'resumed'         => false,
], 'Quiz attempt started.', 201);
?>
