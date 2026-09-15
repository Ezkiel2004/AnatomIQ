<?php
// Recovery requires a signed-in teacher until verified email delivery is configured.
require_once __DIR__ . '/../helpers.php';
requireMethod('POST');
$body = getJsonBody();
if (($body['action'] ?? '') === 'request') {
    $contact = schoolSettings()['recovery_contact'] ?? '';
    jsonSuccess(null, $contact !== '' ? 'Contact ' . $contact . ' to recover your account.' : 'Please contact your teacher to recover your account.');
}
requireTeacher();
$id = (int)requireField($body, 'student_id');
$password = requireField($body, 'new_password');
if (!is_string($password) || strlen($password) < 8) jsonError('Use a password with at least 8 characters.', 422);
$db = Database::getInstance();
if (!$db->fetchOne("SELECT user_id FROM users WHERE user_id=? AND role='student'", [$id])) jsonError('Student not found.', 404);
$db->query('UPDATE users SET password_hash=? WHERE user_id=?', [password_hash($password, PASSWORD_BCRYPT), $id]);
jsonSuccess(null, 'Student password updated.');
