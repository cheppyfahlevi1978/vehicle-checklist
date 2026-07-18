<?php

declare(strict_types=1);

const SERVER_ROOT = __DIR__ . '/..';
const DATABASE_DIR = SERVER_ROOT . '/database';
const STORAGE_DIR = SERVER_ROOT . '/storage';

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/schema.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('siyadi_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

function ensure_directories(): void
{
    foreach ([DATABASE_DIR, DATABASE_DIR . '/branches', STORAGE_DIR] as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Tidak dapat membuat folder: ' . $directory);
        }
    }
}

function sqlite(string $path): PDO
{
    $directory = dirname($path);
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    return $pdo;
}

function central_db(): PDO
{
    static $database;
    if (!$database instanceof PDO) {
        ensure_directories();
        $database = sqlite(DATABASE_DIR . '/central.sqlite');
    }
    return $database;
}

function is_installed(): bool
{
    $path = DATABASE_DIR . '/central.sqlite';
    if (!is_file($path)) {
        return false;
    }

    try {
        $statement = sqlite($path)->query("SELECT name FROM sqlite_master WHERE type='table' AND name='branches'");
        return (bool) $statement->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}

function branch_by_code(string $code): ?array
{
    if (!is_installed()) {
        return null;
    }

    $statement = central_db()->prepare('SELECT * FROM branches WHERE UPPER(code) = UPPER(:code) AND is_active = 1 LIMIT 1');
    $statement->execute(['code' => $code]);
    $branch = $statement->fetch();
    return $branch ?: null;
}

function branch_by_host(string $host): ?array
{
    if (!is_installed()) {
        return null;
    }

    $statement = central_db()->prepare('SELECT * FROM branches WHERE LOWER(domain) = LOWER(:domain) AND is_active = 1 LIMIT 1');
    $statement->execute(['domain' => $host]);
    $branch = $statement->fetch();
    return $branch ?: null;
}

function current_branch(): ?array
{
    global $config;
    static $resolved = false;
    static $branch;

    if ($resolved) {
        return $branch;
    }
    $resolved = true;

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);
    $branch = branch_by_host($host);

    if (!$branch) {
        $code = (string) ($_GET['branch'] ?? $_SESSION['branch_code'] ?? $config['default_branch']);
        $branch = branch_by_code($code);
    }

    if ($branch) {
        $_SESSION['branch_code'] = $branch['code'];
    }

    return $branch;
}

function tenant_db(?array $branch = null): PDO
{
    static $databases = [];
    $branch ??= current_branch();
    if (!$branch) {
        throw new RuntimeException('Cabang tidak ditemukan.');
    }

    $key = strtoupper($branch['code']);
    if (!isset($databases[$key])) {
        $relative = ltrim((string) $branch['database_path'], '/');
        $path = SERVER_ROOT . '/' . $relative;
        $databases[$key] = sqlite($path);
    }
    return $databases[$key];
}

function request_path(): string
{
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $path = '/' . trim($path, '/');
    return $path === '//' ? '/' : $path;
}

function request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['_csrf'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi formulir berakhir. Muat ulang halaman dan coba lagi.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flash(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}

function tenant_user(): ?array
{
    return $_SESSION['tenant_user'] ?? null;
}

function national_user(): ?array
{
    return $_SESSION['national_user'] ?? null;
}

function require_tenant_auth(): void
{
    $user = tenant_user();
    $branch = current_branch();
    if (!$user || !$branch || ($user['branch_code'] ?? null) !== $branch['code']) {
        redirect('/login');
    }
}

function require_national_auth(): void
{
    if (!national_user()) {
        redirect('/national/login');
    }
}

function idr(float|int|string|null $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

function audit(string $action, string $module, ?string $recordId = null, ?string $description = null): void
{
    try {
        $user = tenant_user();
        $statement = tenant_db()->prepare(
            'INSERT INTO audit_logs (user_id, user_name, action, module, record_id, description, ip_address, created_at)
             VALUES (:user_id, :user_name, :action, :module, :record_id, :description, :ip_address, :created_at)'
        );
        $statement->execute([
            'user_id' => $user['id'] ?? null,
            'user_name' => $user['name'] ?? 'Sistem',
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable) {
        // Audit tidak menggagalkan transaksi utama pada versi pilot.
    }
}

function next_code(string $prefix, string $table): string
{
    $count = (int) tenant_db()->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() + 1;
    return sprintf('%s-%06d', $prefix, $count);
}
