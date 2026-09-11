<?php
/**
 * AnatomIQ – Password Reset API Endpoint
 * POST /api/auth/reset-password.php
 *
 * Two-step password reset flow:
 *
 * Step 1 – Request Reset (action: "request"):
 *   Body: { "action": "request", "identifier": "email or student_id" }
 *   Generates a reset token, stores it in the database.
 *   Returns the token (in development) or sends email (in production).
 *
 * Step 2 – Complete Reset (action: "reset"):
 *   Body: { "action": "reset", "token": "...", "new_password": "..." }
 *   Validates the token and updates the password.
 */

require_once __DIR__ . '/../helpers.php';
requireMethod('POST');

$body   = getJsonBody();
$action = requireField($body, 'action', 'Action');

$db = Database::getInstance();

// ── Step 1: Request a password reset ────────────────────────────
if ($action === 'request') {
    $identifier = requireField($body, 'identifier', 'Email or Student ID');

    // Search by email first, then by student_id
    $user = $db->fetchOne(
        "SELECT u.user_id, u.email, u.full_name
         FROM users u
         LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
         WHERE u.email = ? OR sp.student_id = ?",
        [$identifier, $identifier]
    );

    if (!$user) {
        jsonError('No account found with that email or student ID.', 404);
    }

    // Generate a secure random token
    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Delete any existing tokens for this user
    $db->query(
        "DELETE FROM password_reset_tokens WHERE user_id = ?",
        [$user['user_id']]
    );

    // Store the new token
    $db->query(
        "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
         VALUES (?, ?, ?)",
        [$user['user_id'], hash('sha256', $token), $expiresAt]
    );

    // In production: send email with reset link
    // For local development: return the token directly
    jsonSuccess([
        'message'   => 'Password reset token generated.',
        'token'     => $token,  // Remove in production — only for local dev
        'expires'   => $expiresAt,
        'user_name' => $user['full_name'],
    ], 'Reset instructions generated. Use the token to reset your password.');
}

// ── Step 2: Reset the password ──────────────────────────────────
if ($action === 'reset') {
    $token       = requireField($body, 'token', 'Reset token');
    $newPassword = requireField($body, 'new_password', 'New password');

    // Validate password strength
    if (strlen($newPassword) < 6) {
        jsonError('Password must be at least 6 characters long.', 422);
    }

    // Find the token in the database (compare hashed version)
    $tokenHash = hash('sha256', $token);
    $record = $db->fetchOne(
        "SELECT prt.user_id, prt.expires_at
         FROM password_reset_tokens prt
         WHERE prt.token_hash = ? AND prt.expires_at > NOW()",
        [$tokenHash]
    );

    if (!$record) {
        jsonError('Invalid or expired reset token. Please request a new one.', 400);
    }

    // Update the password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->query(
        "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?",
        [$hashedPassword, $record['user_id']]
    );

    // Delete all tokens for this user
    $db->query(
        "DELETE FROM password_reset_tokens WHERE user_id = ?",
        [$record['user_id']]
    );

    jsonSuccess(null, 'Password has been reset successfully. You can now log in with your new password.');
}

jsonError('Invalid action. Use "request" or "reset".', 400);
?>
