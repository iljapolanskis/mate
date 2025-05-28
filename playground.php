<?php

try {
    $pdo = new PDO('sqlite::memory:');
    echo 'SQLite PDO works!';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
