<?php
/**
 * AnatomIQ – Login API Endpoint
 * POST /api/auth/login.php
 *
 * Authenticates a user against the database using bcrypt password verification.
 *
 * Request Body (JSON):
 *   { "username": "string", "password": "string" }
 *
 * Success Response (200):
 *   { "success": true, "data": { user_id, username, role, full_name, ... } }
 *
 * Error Responses:
 *   422 – Missing fields
 *   401 – Invalid credentials
 */

require_once __DIR__ . '/../helpers.php';
requireMethod('POST');

$body = getJsonBody();

$username = requireField($body, 'username', 'Username');
$password = requireField($body, 'password', 'Password');

// Attempt authentication via the Auth class (uses bcrypt + PDO)
$user = Auth::login($username, $password);

if (!$user) {
    // Determine the specific error for better UX
    $db = Database::getInstance();
    $existing = $db->fetchOne(
        "SELECT user_id, role, is_active FROM users WHERE username = ?",
        [$username]
    );

    if (!$existing) {
        jsonError('Username not found. Check your credentials and try again.', 401);
    } elseif (!$existing['is_active']) {
        jsonError('This account has been deactivated. Please contact your teacher.', 401);
    } else {
        jsonError('Incorrect password. Please try again.', 401);
    }
}

// Build the response data (exclude sensitive fields)
$responseData = [
    'user_id'   => (int) $user['user_id'],
    'username'  => $user['username'],
    'role'      => $user['role'],
    'full_name' => $user['full_name'],
    'email'     => $user['email'] ?? null,
    'context'   => $user['context_info'] ?? null,
    'role_id'   => $user['role_id'] ?? null,
];

jsonSuccess($responseData, 'Login successful.');
?>
