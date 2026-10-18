<?php
require_once __DIR__ . '/auth/session.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

redirect('login.php');
