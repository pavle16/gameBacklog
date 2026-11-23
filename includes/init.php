<?php
session_start();
require_once __DIR__ . '/../config/db.php';      // creates $config and $pdo
define('BASE_URL', rtrim($config['base_url'], '/'));
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
