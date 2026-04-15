<?php
require_once 'classes/Database.php';
try {
    $pdo = Database::getInstance()->getConnection();
    $schema = file_get_contents('sql/schema.sql');
    $seed = file_get_contents('sql/seed.sql');
    $pdo->exec($schema);
    $pdo->exec($seed);
    echo "SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
