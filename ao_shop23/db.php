<?php

$host = 'localhost';
$dbname = 'ao_shop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

function get_product_image($reference, $dbPhoto = null) {
    $ref = preg_replace('/[^A-Za-z0-9\-_]/', '', $reference);
    $photoDir = __DIR__ . '/photo/';
    $uploadDir = __DIR__ . '/uploads/';

    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
        $fileName = $ref . '.' . $ext;
        if (file_exists($photoDir . $fileName)) {
            return '../photo/' . $fileName;
        }
    }

    if (!empty($dbPhoto)) {
        $safeDbPhoto = basename($dbPhoto);
        if (file_exists($photoDir . $safeDbPhoto)) return '../photo/' . $safeDbPhoto;
        if (file_exists($uploadDir . $safeDbPhoto)) return '../uploads/' . $safeDbPhoto;
        return '../uploads/' . $safeDbPhoto;
    }

    return '';
}
