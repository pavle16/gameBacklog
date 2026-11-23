<?php
require_once __DIR__ . '/includes/init.php';

$version = $pdo->query('SELECT VERSION() AS v')->fetch()['v'];
echo "Connected! MySQL/MariaDB version: " . e($version);