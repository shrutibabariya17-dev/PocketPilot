<?php
// logout.php - Sign out of PocketPilot
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();
set_flash('info', 'You have been signed out successfully. Come back soon to track your finances!');
header("Location: login.php");
exit;

