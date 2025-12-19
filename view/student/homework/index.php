<?php
require_once __DIR__ . '/_bootstrap.php';

if (isset($_GET['id'])) {
    require __DIR__ . '/detail.php';
    exit;
}

if (isset($_GET['subject'])) {
    require __DIR__ . '/homeworks.php';
    exit;
}

require __DIR__ . '/subjects.php';
exit;
