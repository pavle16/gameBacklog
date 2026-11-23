<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    logout_user();
    session_start();               // new empty session, so the flash message survives
    flash('success', 'Odjavljeni ste.');
}
redirect('index.php');
