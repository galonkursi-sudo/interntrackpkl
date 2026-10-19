<?php
// Koneksi database InternTrack (PDO). Sesuaikan jika user/password MySQL berbeda.
const DB_HOST = 'localhost';
const DB_NAME = 'interntrack';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $ex) {
            die('Koneksi database gagal. Pastikan MySQL menyala dan database "interntrack" sudah diimpor. Detail: ' . htmlspecialchars($ex->getMessage()));
        }
    }
    return $pdo;
}
