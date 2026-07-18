<?php

declare(strict_types=1);

const APP_NAME = 'SIYADI — Yayasan Dejiaohui Indonesia';
const APP_VERSION = '0.1.0-pilot';
const DEFAULT_BRANCH_CODE = 'SBY';
const DATA_DIR = __DIR__ . '/data';
const CENTRAL_DB = DATA_DIR . '/central.sqlite';
const BRANCH_DIR = DATA_DIR . '/branches';

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Aplikasi membutuhkan PHP 8.1 atau lebih baru.');
}

if (!extension_loaded('pdo_sqlite')) {
    http_response_code(500);
    exit('Ekstensi pdo_sqlite belum aktif. Aktifkan melalui Select PHP Version di cPanel.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('siyadi_session');
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function app_url(array $params = []): string
{
    return 'index.php' . ($params ? '?' . http_build_query($params) : '');
}

function redirect_to(array $params = []): never
{
    header('Location: ' . app_url($params));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = (string)($_POST['csrf_token'] ?? '');
    if ($submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function money(mixed $value): string
{
    return 'Rp' . number_format((float)$value, 0, ',', '.');
}

function db_connect(string $path): PDO
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
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

function installed(): bool
{
    return is_file(CENTRAL_DB) && filesize(CENTRAL_DB) > 0;
}

function central_db(): PDO
{
    static $pdo = null;
    if (!$pdo) {
        $pdo = db_connect(CENTRAL_DB);
    }
    return $pdo;
}

function resolve_branch(): ?array
{
    if (!installed()) {
        return null;
    }

    $code = strtoupper(trim((string)($_GET['branch'] ?? $_SESSION['branch_code'] ?? DEFAULT_BRANCH_CODE)));
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = explode(':', $host)[0];

    $pdo = central_db();
    $branch = null;

    if ($host !== '' && !in_array($host, ['localhost', '127.0.0.1'], true)) {
        $stmt = $pdo->prepare('SELECT * FROM branches WHERE lower(domain) = :domain AND active = 1 LIMIT 1');
        $stmt->execute(['domain' => $host]);
        $branch = $stmt->fetch() ?: null;
    }

    if (!$branch && $code !== '') {
        $stmt = $pdo->prepare('SELECT * FROM branches WHERE upper(code) = :code AND active = 1 LIMIT 1');
        $stmt->execute(['code' => $code]);
        $branch = $stmt->fetch() ?: null;
    }

    if (!$branch) {
        $branch = $pdo->query('SELECT * FROM branches WHERE active = 1 ORDER BY id LIMIT 1')->fetch() ?: null;
    }

    if ($branch) {
        $_SESSION['branch_code'] = $branch['code'];
    }

    return $branch;
}

function tenant_db(?array $branch = null): PDO
{
    static $connections = [];
    $branch ??= resolve_branch();
    if (!$branch) {
        throw new RuntimeException('Cabang belum tersedia.');
    }

    $path = (string)$branch['db_path'];
    if (!str_starts_with($path, DIRECTORY_SEPARATOR)) {
        $path = __DIR__ . '/' . ltrim($path, '/');
    }

    if (!isset($connections[$path])) {
        $connections[$path] = db_connect($path);
    }

    return $connections[$path];
}

function tenant_user(): ?array
{
    return $_SESSION['tenant_user'] ?? null;
}

function national_user(): ?array
{
    return $_SESSION['national_user'] ?? null;
}

function require_tenant_login(): void
{
    $user = tenant_user();
    $branch = resolve_branch();
    if (!$user || !$branch || ($user['branch_code'] ?? '') !== $branch['code']) {
        flash('danger', 'Silakan masuk ke akun cabang.');
        redirect_to(['page' => 'login']);
    }
}

function require_national_login(): void
{
    if (!national_user()) {
        flash('danger', 'Silakan masuk ke portal nasional.');
        redirect_to(['page' => 'national-login']);
    }
}

function audit(string $action, string $module, ?string $recordId = null, ?string $description = null): void
{
    try {
        $user = tenant_user();
        $stmt = tenant_db()->prepare('INSERT INTO audit_logs (user_id, user_name, action, module, record_id, description, ip_address, created_at) VALUES (:user_id, :user_name, :action, :module, :record_id, :description, :ip, :created_at)');
        $stmt->execute([
            'user_id' => $user['id'] ?? null,
            'user_name' => $user['name'] ?? 'Sistem',
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'description' => $description,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable) {
        // Audit tidak boleh menggagalkan transaksi utama pada pilot.
    }
}

function modules(): array
{
    return [
        'members' => [
            'title' => 'Anggota', 'singular' => 'Anggota', 'icon' => '👥', 'table' => 'members',
            'search' => ['member_no', 'name', 'phone', 'email'],
            'columns' => ['member_no', 'name', 'phone', 'status'],
            'fields' => [
                'member_no' => ['label' => 'Nomor Anggota', 'required' => true],
                'name' => ['label' => 'Nama Lengkap', 'required' => true],
                'chinese_name' => ['label' => 'Nama Tionghoa'],
                'gender' => ['label' => 'Jenis Kelamin', 'type' => 'select', 'options' => ['Laki-laki', 'Perempuan'], 'required' => true],
                'phone' => ['label' => 'Nomor Telepon'],
                'email' => ['label' => 'Email', 'type' => 'email'],
                'address' => ['label' => 'Alamat', 'type' => 'textarea'],
                'join_date' => ['label' => 'Tanggal Bergabung', 'type' => 'date'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'donors' => [
            'title' => 'Donatur', 'singular' => 'Donatur', 'icon' => '🤝', 'table' => 'donors',
            'search' => ['donor_no', 'name', 'phone', 'email'],
            'columns' => ['donor_no', 'name', 'donor_type', 'status'],
            'fields' => [
                'donor_no' => ['label' => 'Nomor Donatur', 'required' => true],
                'name' => ['label' => 'Nama Donatur', 'required' => true],
                'donor_type' => ['label' => 'Jenis Donatur', 'type' => 'select', 'options' => ['Perorangan', 'Perusahaan', 'Komunitas', 'Anonim'], 'required' => true],
                'phone' => ['label' => 'Nomor Telepon'],
                'email' => ['label' => 'Email', 'type' => 'email'],
                'address' => ['label' => 'Alamat', 'type' => 'textarea'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'donations' => [
            'title' => 'Donasi', 'singular' => 'Donasi', 'icon' => '💝', 'table' => 'donations',
            'search' => ['receipt_no', 'donor_name', 'purpose', 'status'],
            'columns' => ['receipt_no', 'donor_name', 'donation_date', 'amount', 'status'],
            'money' => ['amount'],
            'fields' => [
                'receipt_no' => ['label' => 'Nomor Kuitansi', 'required' => true],
                'donor_name' => ['label' => 'Nama Donatur', 'required' => true],
                'donation_date' => ['label' => 'Tanggal Donasi', 'type' => 'date', 'required' => true],
                'amount' => ['label' => 'Nominal', 'type' => 'number', 'required' => true],
                'payment_method' => ['label' => 'Metode Pembayaran', 'type' => 'select', 'options' => ['Tunai', 'Transfer Bank', 'QRIS', 'Lainnya'], 'required' => true],
                'purpose' => ['label' => 'Tujuan Donasi'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Draft', 'Menunggu Verifikasi', 'Terverifikasi', 'Dibatalkan'], 'required' => true],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'beneficiaries' => [
            'title' => 'Penerima Manfaat', 'singular' => 'Penerima Manfaat', 'icon' => '🫶', 'table' => 'beneficiaries',
            'search' => ['beneficiary_no', 'name', 'phone', 'need_type'],
            'columns' => ['beneficiary_no', 'name', 'need_type', 'verification_status'],
            'fields' => [
                'beneficiary_no' => ['label' => 'Nomor Penerima', 'required' => true],
                'name' => ['label' => 'Nama', 'required' => true],
                'identity_no' => ['label' => 'Nomor Identitas'],
                'phone' => ['label' => 'Nomor Telepon'],
                'address' => ['label' => 'Alamat', 'type' => 'textarea'],
                'economic_condition' => ['label' => 'Kondisi Ekonomi', 'type' => 'textarea'],
                'need_type' => ['label' => 'Jenis Kebutuhan', 'required' => true],
                'family_members' => ['label' => 'Jumlah Anggota Keluarga', 'type' => 'number'],
                'verification_status' => ['label' => 'Status Verifikasi', 'type' => 'select', 'options' => ['Belum Disurvei', 'Disurvei', 'Terverifikasi', 'Ditolak'], 'required' => true],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'programs' => [
            'title' => 'Program Sosial', 'singular' => 'Program', 'icon' => '🌏', 'table' => 'programs',
            'search' => ['program_code', 'name', 'category', 'location', 'status'],
            'columns' => ['program_code', 'name', 'start_date', 'location', 'status'],
            'money' => ['budget'],
            'fields' => [
                'program_code' => ['label' => 'Kode Program', 'required' => true],
                'name' => ['label' => 'Nama Program', 'required' => true],
                'category' => ['label' => 'Kategori', 'type' => 'select', 'options' => ['Pembagian Sembako', 'Donor Darah', 'Bantuan Pendidikan', 'Bantuan Lansia', 'Bantuan Bencana', 'Pemeriksaan Kesehatan', 'Lainnya'], 'required' => true],
                'start_date' => ['label' => 'Tanggal Mulai', 'type' => 'date', 'required' => true],
                'end_date' => ['label' => 'Tanggal Selesai', 'type' => 'date'],
                'location' => ['label' => 'Lokasi'],
                'target_beneficiaries' => ['label' => 'Target Penerima', 'type' => 'number'],
                'budget' => ['label' => 'Anggaran', 'type' => 'number'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Direncanakan', 'Berjalan', 'Selesai', 'Dibatalkan'], 'required' => true],
                'description' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            ],
        ],
        'distributions' => [
            'title' => 'Penyaluran Bantuan', 'singular' => 'Penyaluran', 'icon' => '📦', 'table' => 'distributions',
            'search' => ['distribution_no', 'program_name', 'beneficiary_name', 'assistance_type'],
            'columns' => ['distribution_no', 'distribution_date', 'program_name', 'beneficiary_name', 'total_value'],
            'money' => ['total_value'],
            'fields' => [
                'distribution_no' => ['label' => 'Nomor Penyaluran', 'required' => true],
                'distribution_date' => ['label' => 'Tanggal', 'type' => 'date', 'required' => true],
                'program_name' => ['label' => 'Program', 'required' => true],
                'beneficiary_name' => ['label' => 'Penerima', 'required' => true],
                'assistance_type' => ['label' => 'Jenis Bantuan', 'required' => true],
                'quantity' => ['label' => 'Jumlah', 'type' => 'number'],
                'total_value' => ['label' => 'Nilai Bantuan', 'type' => 'number', 'required' => true],
                'location' => ['label' => 'Lokasi'],
                'officer_name' => ['label' => 'Petugas'],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'finances' => [
            'title' => 'Keuangan', 'singular' => 'Transaksi', 'icon' => '💰', 'table' => 'finances',
            'search' => ['transaction_no', 'category', 'description', 'reference_no', 'status'],
            'columns' => ['transaction_no', 'transaction_date', 'type', 'category', 'amount', 'status'],
            'money' => ['amount'],
            'fields' => [
                'transaction_no' => ['label' => 'Nomor Transaksi', 'required' => true],
                'transaction_date' => ['label' => 'Tanggal', 'type' => 'date', 'required' => true],
                'type' => ['label' => 'Jenis', 'type' => 'select', 'options' => ['Pemasukan', 'Pengeluaran'], 'required' => true],
                'category' => ['label' => 'Kategori', 'required' => true],
                'description' => ['label' => 'Keterangan', 'type' => 'textarea', 'required' => true],
                'amount' => ['label' => 'Nominal', 'type' => 'number', 'required' => true],
                'payment_method' => ['label' => 'Metode Pembayaran', 'type' => 'select', 'options' => ['Tunai', 'Transfer Bank', 'QRIS', 'Lainnya']],
                'reference_no' => ['label' => 'Nomor Referensi'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Draft', 'Diajukan', 'Disetujui', 'Dibayar', 'Selesai', 'Dibatalkan'], 'required' => true],
            ],
        ],
        'volunteers' => [
            'title' => 'Relawan', 'singular' => 'Relawan', 'icon' => '🙋', 'table' => 'volunteers',
            'search' => ['volunteer_no', 'name', 'phone', 'skills'],
            'columns' => ['volunteer_no', 'name', 'phone', 'skills', 'status'],
            'fields' => [
                'volunteer_no' => ['label' => 'Nomor Relawan', 'required' => true],
                'name' => ['label' => 'Nama', 'required' => true],
                'phone' => ['label' => 'Nomor Telepon'],
                'email' => ['label' => 'Email', 'type' => 'email'],
                'skills' => ['label' => 'Keahlian', 'type' => 'textarea'],
                'availability' => ['label' => 'Ketersediaan'],
                'service_hours' => ['label' => 'Jam Pengabdian', 'type' => 'number'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'moral_classes' => [
            'title' => 'Pendidikan Moral', 'singular' => 'Kelas Moral', 'icon' => '📚', 'table' => 'moral_classes',
            'search' => ['class_code', 'title', 'teacher_name', 'age_group', 'status'],
            'columns' => ['class_code', 'title', 'class_date', 'teacher_name', 'status'],
            'fields' => [
                'class_code' => ['label' => 'Kode Kelas', 'required' => true],
                'title' => ['label' => 'Judul Materi', 'required' => true],
                'class_date' => ['label' => 'Tanggal', 'type' => 'date', 'required' => true],
                'teacher_name' => ['label' => 'Pengajar'],
                'age_group' => ['label' => 'Kelompok Usia'],
                'participant_count' => ['label' => 'Jumlah Peserta', 'type' => 'number'],
                'location' => ['label' => 'Lokasi'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Direncanakan', 'Selesai', 'Dibatalkan'], 'required' => true],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'culture' => [
            'title' => 'Kegiatan Budaya', 'singular' => 'Kegiatan Budaya', 'icon' => '🏮', 'table' => 'culture_activities',
            'search' => ['activity_code', 'title', 'category', 'location', 'status'],
            'columns' => ['activity_code', 'title', 'activity_date', 'category', 'status'],
            'fields' => [
                'activity_code' => ['label' => 'Kode Kegiatan', 'required' => true],
                'title' => ['label' => 'Nama Kegiatan', 'required' => true],
                'activity_date' => ['label' => 'Tanggal', 'type' => 'date', 'required' => true],
                'category' => ['label' => 'Kategori', 'required' => true],
                'location' => ['label' => 'Lokasi'],
                'participant_count' => ['label' => 'Jumlah Peserta', 'type' => 'number'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Direncanakan', 'Berjalan', 'Selesai', 'Dibatalkan'], 'required' => true],
                'description' => ['label' => 'Deskripsi', 'type' => 'textarea'],
            ],
        ],
        'inventory' => [
            'title' => 'Inventaris', 'singular' => 'Barang', 'icon' => '📋', 'table' => 'inventory',
            'search' => ['item_code', 'name', 'category', 'location', 'condition_status'],
            'columns' => ['item_code', 'name', 'category', 'stock', 'condition_status'],
            'fields' => [
                'item_code' => ['label' => 'Kode Barang', 'required' => true],
                'name' => ['label' => 'Nama Barang', 'required' => true],
                'category' => ['label' => 'Kategori'],
                'stock' => ['label' => 'Stok', 'type' => 'number', 'required' => true],
                'unit' => ['label' => 'Satuan'],
                'location' => ['label' => 'Lokasi Penyimpanan'],
                'condition_status' => ['label' => 'Kondisi', 'type' => 'select', 'options' => ['Baik', 'Perlu Perbaikan', 'Rusak'], 'required' => true],
                'expiry_date' => ['label' => 'Tanggal Kedaluwarsa', 'type' => 'date'],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
        'documents' => [
            'title' => 'Dokumen dan Surat', 'singular' => 'Dokumen', 'icon' => '📄', 'table' => 'documents',
            'search' => ['document_no', 'title', 'document_type', 'status'],
            'columns' => ['document_no', 'title', 'document_type', 'document_date', 'status'],
            'fields' => [
                'document_no' => ['label' => 'Nomor Dokumen', 'required' => true],
                'title' => ['label' => 'Judul', 'required' => true],
                'document_type' => ['label' => 'Jenis Dokumen', 'type' => 'select', 'options' => ['Surat Masuk', 'Surat Keluar', 'Legalitas', 'Proposal', 'LPJ', 'Perjanjian', 'Lainnya'], 'required' => true],
                'document_date' => ['label' => 'Tanggal Dokumen', 'type' => 'date'],
                'expiry_date' => ['label' => 'Tanggal Berakhir', 'type' => 'date'],
                'file_reference' => ['label' => 'Referensi File/URL'],
                'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['Aktif', 'Arsip', 'Berakhir'], 'required' => true],
                'notes' => ['label' => 'Catatan', 'type' => 'textarea'],
            ],
        ],
    ];
}

function module_definition(string $key): array
{
    $modules = modules();
    if (!isset($modules[$key])) {
        http_response_code(404);
        exit('Modul tidak ditemukan.');
    }
    return $modules[$key];
}

function collect_module_data(array $definition): array
{
    $data = [];
    $errors = [];

    foreach ($definition['fields'] as $field => $meta) {
        $value = trim((string)($_POST[$field] ?? ''));
        if (($meta['required'] ?? false) && $value === '') {
            $errors[] = $meta['label'] . ' wajib diisi.';
        }
        if (($meta['type'] ?? '') === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $meta['label'] . ' tidak valid.';
        }
        if (($meta['type'] ?? '') === 'number' && $value !== '' && !is_numeric($value)) {
            $errors[] = $meta['label'] . ' harus berupa angka.';
        }
        $data[$field] = $value === '' ? null : $value;
    }

    if ($errors) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
    }

    return [$data, $errors];
}

function render_flashes(): string
{
    $html = '';
    foreach (pull_flashes() as $item) {
        $class = $item['type'] === 'success' ? 'success' : 'danger';
        $html .= '<div class="alert ' . $class . '">' . h($item['message']) . '</div>';
    }
    return $html;
}

function render_tenant_layout(string $title, string $content): void
{
    $branch = resolve_branch();
    $user = tenant_user();
    $modules = modules();
    $page = (string)($_GET['page'] ?? 'dashboard');

    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#741f2f"><title>' . h($title) . ' — SIYADI</title><link rel="manifest" href="manifest.webmanifest"><link rel="stylesheet" href="assets/app.css"></head><body>';
    echo '<div class="app-shell"><aside class="sidebar"><a class="brand" href="' . h(app_url(['page' => 'dashboard'])) . '"><span class="brand-mark">德</span><span><strong>SIYADI</strong><small>Dejiaohui Indonesia</small></span></a>';
    echo '<div class="branch-card"><small>Cabang aktif</small><strong>' . h($branch['name'] ?? 'Cabang') . '</strong><span>' . h($branch['code'] ?? '') . '</span></div><nav>';
    echo '<a class="' . ($page === 'dashboard' ? 'active' : '') . '" href="' . h(app_url(['page' => 'dashboard'])) . '">🏠 Dashboard</a>';
    foreach ($modules as $key => $module) {
        $active = $page === 'module' && ($_GET['module'] ?? '') === $key ? 'active' : '';
        echo '<a class="' . $active . '" href="' . h(app_url(['page' => 'module', 'module' => $key])) . '">' . $module['icon'] . ' ' . h($module['title']) . '</a>';
    }
    echo '<a class="' . ($page === 'reports' ? 'active' : '') . '" href="' . h(app_url(['page' => 'reports'])) . '">📊 Laporan</a></nav></aside>';
    echo '<main class="main"><header class="topbar"><button class="menu-toggle" type="button" onclick="document.body.classList.toggle(\'menu-open\')">☰</button><div><strong>' . h($title) . '</strong><small>' . h(date('d F Y')) . '</small></div><div class="user-box"><span>' . h($user['name'] ?? '') . '</span><small>' . h($user['role'] ?? '') . '</small><a href="' . h(app_url(['page' => 'logout'])) . '">Keluar</a></div></header><section class="content">';
    echo render_flashes();
    echo $content;
    echo '</section></main></div><script>if("serviceWorker" in navigator){window.addEventListener("load",()=>navigator.serviceWorker.register("sw.js").catch(()=>{}));}</script></body></html>';
}

function render_national_layout(string $title, string $content): void
{
    $user = national_user();
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . ' — Portal Nasional</title><link rel="stylesheet" href="assets/app.css"></head><body><div class="national-shell">';
    echo '<header class="national-header"><a class="brand" href="' . h(app_url(['page' => 'national-dashboard'])) . '"><span class="brand-mark">德</span><span><strong>Portal Nasional</strong><small>Yayasan Dejiaohui Indonesia</small></span></a><nav><a href="' . h(app_url(['page' => 'national-dashboard'])) . '">Dashboard</a><a href="' . h(app_url(['page' => 'branches'])) . '">Cabang</a><span>' . h($user['name'] ?? '') . '</span><a href="' . h(app_url(['page' => 'national-logout'])) . '">Keluar</a></nav></header><main class="national-main">';
    echo render_flashes();
    echo $content;
    echo '</main></div></body></html>';
}

function render_auth(string $title, string $subtitle, string $action, string $extraLink = ''): void
{
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . ' — SIYADI</title><link rel="stylesheet" href="assets/app.css"></head><body class="auth-body"><div class="auth-card"><div class="auth-logo">德</div><h1>' . h($title) . '</h1><p class="muted center">' . h($subtitle) . '</p>';
    echo render_flashes();
    echo '<form method="post" action="' . h($action) . '" class="form-stack"><input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '"><label>Email<input type="email" name="email" required autofocus></label><label>Password<input type="password" name="password" required></label><button class="btn primary" type="submit">Masuk</button></form>';
    if ($extraLink !== '') {
        echo '<div class="auth-help">' . $extraLink . '</div>';
    }
    echo '</div></body></html>';
}
