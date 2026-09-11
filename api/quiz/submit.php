<?php
/**
 * AnatomIQ – Submit Quiz API
 * POST /api/quiz/submit.php
 *
 * Grades submissions for Multiple Choice, Hotspot (Click-to-Identify),
 * and Identification exercises. Stores answer responses and updates
 * overall student progress metrics.
 */

require_once __DIR__ . '/../helpers.php';
requireMethod('POST');
$user = requireStudent();

$db   = Database::getInstance();
$body = getJsonBody();

$submissionId  = (int) requireField($body, 'submission_id', 'Submission ID');
$timeTakenSecs = (int) optionalField($body, 'time_taken_secs', 0);
$answers       = optionalField($body, 'answers', []);

// Verify submission belongs to this student and is in_progress
$submission = $db->fetchOne(
    "SELECT sub.*, a.passing_score, a.assessment_id, a.title AS assessment_title
     FROM assessment_submissions sub
     JOIN assessments a ON a.assessment_id = sub.assessment_id
     WHERE sub.submission_id = ? AND sub.student_id = ?",
    [$submissionId, $user['user_id']]
);

if (!$submission) {
    jsonError('Submission not found or unauthorized.', 404);
}

if ($submission['status'] !== 'in_progress') {
    jsonError('This assessment attempt has already been submitted and graded.', 400);
}

$assessmentId = (int) $submission['assessment_id'];

// Load all questions for this assessment
$questions = $db->fetchAll(
    "SELECT question_id, question_text, question_type, hotspot_data, points
     FROM questions
     WHERE assessment_id = ?
     ORDER BY sort_order ASC, question_id ASC",
    [$assessmentId]
);

if (empty($questions)) {
    jsonError('Assessment has no questions configured.', 400);
}

// Load correct answers for multiple choice
$qIds = array_column($questions, 'question_id');
$inClause = implode(',', array_fill(0, count($qIds), '?'));

$correctOptions = $db->fetchAll(
    "SELECT question_id, option_id, option_text
     FROM answer_options
     WHERE question_id IN ({$inClause}) AND is_correct = 1",
    $qIds
);

$correctMap = [];
foreach ($correctOptions as $co) {
    $correctMap[$co['question_id']] = (int) $co['option_id'];
}

// Index submitted answers by question_id
$answersByQ = [];
foreach ($answers as $ans) {
    if (isset($ans['question_id'])) {
        $answersByQ[(int)$ans['question_id']] = $ans;
    }
}

$totalMaxPoints = 0.00;
$totalEarned    = 0.00;
$breakdown      = [];

$pdo = $db->getConnection();
$pdo->beginTransaction();

try {
    foreach ($questions as $q) {
        $qid        = (int) $q['question_id'];
        $qType      = $q['question_type'];
        $pointsMax  = (float) $q['points'];
        $totalMaxPoints += $pointsMax;

        $userAns    = $answersByQ[$qid] ?? null;
        $isCorrect  = false;
        $earned     = 0.00;
        $selectedOpt= null;
        $respText   = null;
        $hotspotJson= null;

        if ($userAns) {
            if ($qType === 'multiple_choice' || $qType === 'true_false' || $qType === 'diagram') {
                $selectedOpt = isset($userAns['selected_option']) ? (int) $userAns['selected_option'] : null;
                if ($selectedOpt && isset($correctMap[$qid]) && $selectedOpt === $correctMap[$qid]) {
                    $isCorrect = true;
                    $earned    = $pointsMax;
                }
            } elseif ($qType === 'hotspot') {
                // Hotspot grading (click-to-identify)
                $clicked = $userAns['hotspot_clicked'] ?? null;
                $hotspotJson = $clicked ? json_encode($clicked, JSON_UNESCAPED_UNICODE) : null;
                
                $target = is_string($q['hotspot_data']) ? json_decode($q['hotspot_data'], true) : $q['hotspot_data'];
                $targetLabel = strtolower(trim($target['target_label'] ?? ''));

                if ($clicked && !empty($targetLabel)) {
                    $clickedLabel = strtolower(trim($clicked['label'] ?? $clicked['id'] ?? ''));
                    if ($clickedLabel === $targetLabel || str_contains($clickedLabel, $targetLabel) || str_contains($targetLabel, $clickedLabel)) {
                        $isCorrect = true;
                        $earned    = $pointsMax;
                    }
                }
            } elseif ($qType === 'identification' || $qType === 'fill_blank') {
                // Text match
                $respText = trim($userAns['response_text'] ?? '');
                $target = is_string($q['hotspot_data']) ? json_decode($q['hotspot_data'], true) : $q['hotspot_data'];
                $correctWord = strtolower(trim($target['target_label'] ?? ''));
                if (empty($correctWord) && isset($correctOptions)) {
                    foreach ($correctOptions as $co) {
                        if ($co['question_id'] === $qid) {
                            $correctWord = strtolower(trim($co['option_text']));
                            break;
                        }
                    }
                }

                if (strtolower($respText) === $correctWord && $respText !== '') {
                    $isCorrect = true;
                    $earned    = $pointsMax;
                }
            }
        }

        $totalEarned += $earned;

        // Save answer response record
        $db->query(
            "INSERT INTO answer_responses
                (submission_id, question_id, selected_option, response_text, hotspot_clicked, is_correct, points_earned)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $submissionId,
                $qid,
                $selectedOpt,
                $respText,
                $hotspotJson,
                $isCorrect ? 1 : 0,
                $earned
            ]
        );

        $breakdown[] = [
            'question_id'   => $qid,
            'question_text' => $q['question_text'],
            'question_type' => $qType,
            'is_correct'    => $isCorrect,
            'points_earned' => $earned,
            'points_max'    => $pointsMax,
        ];
    }

    $finalScorePct = $totalMaxPoints > 0 ? round(($totalEarned / $totalMaxPoints) * 100, 2) : 0;
    $passingScore  = (float) $submission['passing_score'];
    $passed        = ($finalScorePct >= $passingScore);

    // Update submission record to graded
    $db->query(
        "UPDATE assessment_submissions SET
            raw_score       = ?,
            max_score       = ?,
            score           = ?,
            time_taken_secs = ?,
            status          = 'graded',
            submitted_at    = NOW(),
            graded_at       = NOW()
         WHERE submission_id = ?",
        [$totalEarned, $totalMaxPoints, $finalScorePct, $timeTakenSecs, $submissionId]
    );

    // Update student progress summary
    _updateProgressSummary($user['user_id'], $db);

    $pdo->commit();

    jsonSuccess([
        'submission_id'    => $submissionId,
        'assessment_title' => $submission['assessment_title'],
        'score'            => $finalScorePct,
        'raw_score'        => $totalEarned,
        'max_score'        => $totalMaxPoints,
        'passed'           => $passed,
        'passing_score'    => $passingScore,
        'time_taken_secs'  => $timeTakenSecs,
        'breakdown'        => $breakdown
    ], 'Assessment submitted and graded successfully.');

} catch (Exception $e) {
    $pdo->rollBack();
    jsonError('Failed to grade submission: ' . $e->getMessage(), 500);
}
?>
