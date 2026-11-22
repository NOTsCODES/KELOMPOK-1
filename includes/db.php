<?php
/**
 * Koneksi database memakai PDO + prepared statement (aman dari SQL injection).
 * Pakai: $rows = db_all('SELECT * FROM users WHERE id = ?', [$id]);
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Samakan zona waktu MySQL dengan PHP
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        } catch (PDOException $e) {
            http_response_code(500);
            $msg = APP_DEBUG ? htmlspecialchars($e->getMessage()) : 'Database unavailable.';
            exit('<p style="font-family:sans-serif;padding:40px">Tidak bisa terhubung ke database. '
                . 'Pastikan MySQL di XAMPP sudah Start dan file <code>sql/portofiles.sql</code> sudah di-import.<br><small>'
                . $msg . '</small></p>');
        }
    }
    return $pdo;
}

function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_run($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

function db_value(string $sql, array $params = [])
{
    return db_run($sql, $params)->fetchColumn();
}
