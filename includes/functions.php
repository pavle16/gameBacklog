<?php
// Escape output (XSS protection) – use everywhere you echo user data
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never {
    header("Location: $path");
    exit;
}