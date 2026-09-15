<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/notification-data.php';
requireMethod('GET');
$user = requireLogin();
jsonSuccess(notificationData($user, Database::getInstance()));
