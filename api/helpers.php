<?php
/**
 * AnatomIQ – Shared API Helpers
 * Common utilities for all API endpoints: response formatting,
 * CORS headers, session management, authentication middleware.
 */

// ── Error Reporting (disable display in production) ─────────────
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ── Include Database & Auth Classes ─────────────────────────────
require_once __DIR__ . '/../database/connection.php';

// ── CORS & Content-Type Headers ─────────────────────────────────
function setCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = ['http://localhost', 'http://127.0.0.1'];

    // Allow same-origin and common localhost origins
    foreach ($allowed as $ao) {
        if (str_starts_with($origin, $ao)) {
            header("Access-Control-Allow-Origin: {$origin}");
            break;
        }
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Content-Type: application/json; charset=utf-8');
}

// Handle preflight OPTIONS requests
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    setCorsHeaders();
    http_response_code(204);
    exit;
}

setCorsHeaders();

// ── Start Secure Session ────────────────────────────────────────
Auth::startSecureSession();

// ── JSON Response Helpers ───────────────────────────────────────

/**
 * Send a JSON success response and exit.
 */
function jsonSuccess(mixed $data = null, string $message = 'Success', int $code = 200): never {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send a JSON error response and exit.
 */
function jsonError(string $message = 'An error occurred', int $code = 400, mixed $errors = null): never {
    http_response_code($code);
    $response = [
        'success' => false,
        'message' => $message,
    ];
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth Middleware ──────────────────────────────────────────────

/**
 * Require the user to be logged in. Returns user session data.
 * Sends 401 JSON error if not authenticated.
 */
function requireLogin(): array {
    $user = Auth::getCurrentUser();
    if (!$user) {
        jsonError('Authentication required. Please log in.', 401);
    }
    return $user;
}

/**
 * Require the user to have a specific role.
 * Sends 403 JSON error if role doesn't match.
 */
function requireRole(string $role): array {
    $user = requireLogin();
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        jsonError('Access denied. Insufficient permissions.', 403);
    }
    return $user;
}

/**
 * Require the user to be a teacher or admin.
 */
function requireTeacher(): array {
    $user = requireLogin();
    if ($user['role'] !== 'teacher' && $user['role'] !== 'admin') {
        jsonError('Access denied. Teacher privileges required.', 403);
    }
    return $user;
}

/**
 * Require the user to be a student.
 */
function requireStudent(): array {
    $user = requireLogin();
    if ($user['role'] !== 'student') {
        jsonError('Access denied. Student account required.', 403);
    }
    return $user;
}

// ── Request Parsing Helpers ─────────────────────────────────────

/**
 * Enforce that the request uses the specified HTTP method.
 */
function requireMethod(string ...$methods): void {
    $current = $_SERVER['REQUEST_METHOD'];
    if (!in_array($current, $methods, true)) {
        jsonError("Method {$current} not allowed. Use: " . implode(', ', $methods), 405);
    }
}

/**
 * Get JSON body from POST/PUT request.
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError('Invalid JSON in request body.', 400);
    }
    return $data;
}

/**
 * Get a required field from an array, or send error.
 */
function requireField(array $data, string $field, string $label = ''): mixed {
    $label = $label ?: $field;
    if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
        jsonError("{$label} is required.", 422);
    }
    return is_string($data[$field]) ? trim($data[$field]) : $data[$field];
}

/**
 * Get an optional field from an array with a default value.
 */
function optionalField(array $data, string $field, mixed $default = null): mixed {
    if (!isset($data[$field])) {
        return $default;
    }
    return is_string($data[$field]) ? trim($data[$field]) : $data[$field];
}

// ── Sanitization ────────────────────────────────────────────────

/**
 * Sanitize a string for safe output (HTML entity encoding).
 */
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate an integer ID from query parameter.
 */
function getIdParam(string $param = 'id'): int {
    $val = $_GET[$param] ?? null;
    $id = filter_var($val, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        jsonError("Invalid or missing {$param} parameter.", 400);
    }
    return (int) $id;
}

// ── Pagination Helper ───────────────────────────────────────────

/**
 * Get pagination parameters from query string.
 */
function getPagination(int $defaultLimit = 20, int $maxLimit = 100): array {
    $page  = max(1, (int) ($_GET['page'] ?? 1));
    $limit = min($maxLimit, max(1, (int) ($_GET['limit'] ?? $defaultLimit)));
    $offset = ($page - 1) * $limit;
    return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
}

// ── Student Progress Calculation Helper ─────────────────────────

/**
 * Recalculates and caches student overall progress in student_progress_summary table.
 * All metrics are dynamically computed from live database records (zero hardcoded values).
 */
function _updateProgressSummary(int $studentId, Database $db): void {
    // 1. Dynamic count of all published lessons in published modules
    $totalLessonsRow = $db->fetchOne(
        "SELECT COUNT(*) AS total_lessons
         FROM lessons l
         JOIN modules m ON m.module_id = l.module_id AND m.status = 'published'
         WHERE l.status = 'published'"
    );
    $totalLessons = (int) ($totalLessonsRow['total_lessons'] ?? 0);

    // 2. Student lesson progress: completed count and study time
    $lessonStats = $db->fetchOne(
        "SELECT
            COUNT(DISTINCT CASE WHEN slp.status = 'completed' THEN slp.lesson_id END) AS completed_lessons,
            COALESCE(SUM(slp.time_spent_secs), 0) AS lesson_secs
         FROM student_lesson_progress slp
         JOIN lessons l ON l.lesson_id = slp.lesson_id AND l.status = 'published'
         JOIN modules m ON m.module_id = l.module_id AND m.status = 'published'
         WHERE slp.student_id = ?",
        [$studentId]
    );
    $completedLessons = (int) ($lessonStats['completed_lessons'] ?? 0);
    $lessonSecs       = (float) ($lessonStats['lesson_secs'] ?? 0);

    // 3. Quiz assessment submissions: average score, quizzes taken, quizzes passed
    $quizStats = $db->fetchOne(
        "SELECT
            COUNT(q.submission_id) AS quizzes_taken,
            COUNT(CASE WHEN q.score >= q.passing_score_calc THEN 1 END) AS quizzes_passed,
            AVG(q.score) AS avg_score
         FROM (
            SELECT sub.submission_id, sub.score, COALESCE(a.passing_score, 75) AS passing_score_calc
            FROM assessment_submissions sub
            JOIN assessments a ON a.assessment_id = sub.assessment_id
            WHERE sub.student_id = ? AND sub.status IN ('submitted', 'graded')
         ) q",
        [$studentId]
    );
    $quizzesTaken  = (int) ($quizStats['quizzes_taken'] ?? 0);
    $quizzesPassed = (int) ($quizStats['quizzes_passed'] ?? 0);
    $avgScore      = ($quizzesTaken > 0 && $quizStats['avg_score'] !== null)
        ? round((float) $quizStats['avg_score'], 2)
        : null;

    // 4. Systems explored count & 3D exploration time
    $systemsRow = $db->fetchOne(
        "SELECT
            COUNT(DISTINCT system_id) AS systems_explored,
            COALESCE(SUM(duration_secs), 0) AS exploration_secs
         FROM system_exploration_log
         WHERE student_id = ?",
        [$studentId]
    );
    $systemsExplored = (int) ($systemsRow['systems_explored'] ?? 0);
    $explorationSecs = (float) ($systemsRow['exploration_secs'] ?? 0);

    $totalHours = round(($lessonSecs + $explorationSecs) / 3600, 2);

    // 5. Upsert into student_progress_summary
    $db->query(
        "INSERT INTO student_progress_summary
             (student_id, total_lessons, completed_lessons, avg_quiz_score, quizzes_taken, quizzes_passed, systems_explored, total_time_hours)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             total_lessons     = VALUES(total_lessons),
             completed_lessons = VALUES(completed_lessons),
             avg_quiz_score    = VALUES(avg_quiz_score),
             quizzes_taken     = VALUES(quizzes_taken),
             quizzes_passed    = VALUES(quizzes_passed),
             systems_explored  = VALUES(systems_explored),
             total_time_hours  = VALUES(total_time_hours)",
        [
            $studentId,
            $totalLessons,
            $completedLessons,
            $avgScore,
            $quizzesTaken,
            $quizzesPassed,
            $systemsExplored,
            $totalHours,
        ]
    );
}
?>
