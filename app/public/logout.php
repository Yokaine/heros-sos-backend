<?php
require_once __DIR__ . '/../includes/bootstrap.php';
session_unset();
session_destroy();
session_start();
flash('success', 'Vous avez été déconnecté.');
redirect(APP_URL . '/index.php');
