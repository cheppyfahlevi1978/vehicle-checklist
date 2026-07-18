<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        if (installed()) {
            throw new RuntimeException('Aplikasi sudah terpasang. Demi keamanan, instalasi ulang diblokir.');
        }

        if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0775, true) && !is_dir(DATA_DIR)) {
            throw new RuntimeException('Folder data tidak dapat dibuat.');
        }
        if (!is_dir(BRANCH_DIR) && !mkdir(BRANCH_DIR, 0775, true) && !is_dir(BRANCH_DIR)) {
            throw new RuntimeException('Folder database cabang tidak dapat dibuat.');
        }

        $central = db_connect(CENTRAL_DB);
        $central->beginTransaction();
        $central->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS national_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'Pengurus Nasional',
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    city TEXT,
    province TEXT,
    domain TEXT UNIQUE,
    db_path TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS national_announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT,
    publish_date TEXT,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS branch_summaries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    period_date TEXT NOT NULL,
    member_count INTEGER NOT NULL DEFAULT 0,
    volunteer_count INTEGER NOT NULL DEFAULT 0,
    program_count INTEGER NOT NULL DEFAULT 0,
    beneficiary_count INTEGER NOT NULL DEFAULT 0,
    donation_total REAL NOT NULL DEFAULT 0,
    distribution_total REAL NOT NULL DEFAULT 0,
    created_at TEXT,
    updated_at TEXT,
    UNIQUE(branch_id, period_date),
    FOREIGN KEY(branch_id) REFERENCES branches(id) ON DELETE CASCADE
);
SQL);

        $now = date('Y-m-d H:i:s');
        $stmt = $central->prepare('INSERT INTO national_users (name,email,password,role,active,created_at,updated_at) VALUES (?,?,?,?,1,?,?)');
        $stmt->execute([
            'Administrator Nasional',
            'admin@dejiaohui.id',
            password_hash('Admin123!', PASSWORD_DEFAULT),
            'Super Admin Nasional',
            $now,
            $now,
        ]);

        $branchPath = 'data/branches/SBY.sqlite';
        $stmt = $central->prepare('INSERT INTO branches (code,name,city,province,domain,db_path,active,created_at,updated_at) VALUES (?,?,?,?,?,?,1,?,?)');
        $stmt->execute(['SBY', 'Yayasan Dejiaohui Surabaya', 'Surabaya', 'Jawa Timur', null, $branchPath, $now, $now]);
        $central->commit();

        $tenant = db_connect(__DIR__ . '/' . $branchPath);
        $tenant->beginTransaction();
        $tenant->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'Petugas Cabang',
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    chinese_name TEXT,
    gender TEXT,
    phone TEXT,
    email TEXT,
    address TEXT,
    join_date TEXT,
    status TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS donors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    donor_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    donor_type TEXT,
    phone TEXT,
    email TEXT,
    address TEXT,
    status TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS donations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_no TEXT NOT NULL UNIQUE,
    donor_name TEXT NOT NULL,
    donation_date TEXT,
    amount REAL NOT NULL DEFAULT 0,
    payment_method TEXT,
    purpose TEXT,
    status TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS beneficiaries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beneficiary_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    identity_no TEXT,
    phone TEXT,
    address TEXT,
    economic_condition TEXT,
    need_type TEXT,
    family_members INTEGER,
    verification_status TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS programs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    program_code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT,
    start_date TEXT,
    end_date TEXT,
    location TEXT,
    target_beneficiaries INTEGER,
    budget REAL,
    status TEXT,
    description TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS distributions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    distribution_no TEXT NOT NULL UNIQUE,
    distribution_date TEXT,
    program_name TEXT,
    beneficiary_name TEXT,
    assistance_type TEXT,
    quantity REAL,
    total_value REAL NOT NULL DEFAULT 0,
    location TEXT,
    officer_name TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS finances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_no TEXT NOT NULL UNIQUE,
    transaction_date TEXT,
    type TEXT,
    category TEXT,
    description TEXT,
    amount REAL NOT NULL DEFAULT 0,
    payment_method TEXT,
    reference_no TEXT,
    status TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS volunteers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    volunteer_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    skills TEXT,
    availability TEXT,
    service_hours REAL NOT NULL DEFAULT 0,
    status TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS moral_classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_code TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    class_date TEXT,
    teacher_name TEXT,
    age_group TEXT,
    participant_count INTEGER,
    location TEXT,
    status TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS culture_activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_code TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    activity_date TEXT,
    category TEXT,
    location TEXT,
    participant_count INTEGER,
    status TEXT,
    description TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS inventory (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT,
    stock REAL NOT NULL DEFAULT 0,
    unit TEXT,
    location TEXT,
    condition_status TEXT,
    expiry_date TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_no TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    document_type TEXT,
    document_date TEXT,
    expiry_date TEXT,
    file_reference TEXT,
    status TEXT,
    notes TEXT,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    user_name TEXT,
    action TEXT,
    module TEXT,
    record_id TEXT,
    description TEXT,
    ip_address TEXT,
    created_at TEXT
);
SQL);

        $stmt = $tenant->prepare('INSERT INTO users (name,email,password,role,active,created_at,updated_at) VALUES (?,?,?,?,1,?,?)');
        $stmt->execute([
            'Administrator Cabang Surabaya',
            'admin.sby@dejiaohui.id',
            password_hash('Cabang123!', PASSWORD_DEFAULT),
            'Administrator Cabang',
            $now,
            $now,
        ]);

        $tenant->prepare('INSERT INTO members (member_no,name,chinese_name,gender,phone,email,address,join_date,status,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            'SBY-AGT-000001', 'Budi Dharmawan', null, 'Laki-laki', '081234567890', 'budi@example.test', 'Surabaya', date('Y-m-d', strtotime('-3 years')), 'Aktif', 'Data contoh; silakan dihapus.', $now, $now,
        ]);
        $tenant->prepare('INSERT INTO members (member_no,name,chinese_name,gender,phone,email,address,join_date,status,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            'SBY-AGT-000002', 'Maya Lestari', null, 'Perempuan', '081298765432', 'maya@example.test', 'Sidoarjo', date('Y-m-d', strtotime('-1 year')), 'Aktif', 'Data contoh; silakan dihapus.', $now, $now,
        ]);
        $tenant->prepare('INSERT INTO donors (donor_no,name,donor_type,phone,email,address,status,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([
            'SBY-DTR-000001', 'Donatur Contoh', 'Perorangan', '081200000001', 'donatur@example.test', 'Surabaya', 'Aktif', 'Data contoh.', $now, $now,
        ]);
        $tenant->prepare('INSERT INTO donations (receipt_no,donor_name,donation_date,amount,payment_method,purpose,status,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([
            'SBY-KWT-2026-000001', 'Donatur Contoh', date('Y-m-d'), 5000000, 'Transfer Bank', 'Bakti Sosial', 'Terverifikasi', 'Data contoh.', $now, $now,
        ]);
        $tenant->prepare('INSERT INTO programs (program_code,name,category,start_date,end_date,location,target_beneficiaries,budget,status,description,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            'SBY-KGT-2026-000001', 'Bakti Sosial Sembako', 'Pembagian Sembako', date('Y-m-d', strtotime('+14 days')), date('Y-m-d', strtotime('+14 days')), 'Surabaya', 100, 25000000, 'Direncanakan', 'Program contoh untuk uji coba cabang.', $now, $now,
        ]);
        $tenant->prepare('INSERT INTO finances (transaction_no,transaction_date,type,category,description,amount,payment_method,reference_no,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([
            'SBY-KAS-2026-000001', date('Y-m-d'), 'Pemasukan', 'Donasi', 'Donasi awal program', 5000000, 'Transfer Bank', 'SBY-KWT-2026-000001', 'Disetujui', $now, $now,
        ]);
        $tenant->commit();

        $success = true;
    } catch (Throwable $e) {
        if (isset($central) && $central instanceof PDO && $central->inTransaction()) {
            $central->rollBack();
        }
        if (isset($tenant) && $tenant instanceof PDO && $tenant->inTransaction()) {
            $tenant->rollBack();
        }
        $error = $e->getMessage();
    }
}

?><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Instalasi SIYADI</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="auth-body">
<div class="auth-card install-card">
    <div class="auth-logo">德</div>
    <h1>Instalasi SIYADI</h1>
    <p class="muted center">Pilot Yayasan Dejiaohui Indonesia</p>

    <?php if ($error): ?>
        <div class="alert danger"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($success || installed()): ?>
        <div class="alert success">Aplikasi sudah terpasang dan database cabang Surabaya sudah dibuat.</div>
        <div class="credential-box">
            <strong>Akun Cabang</strong>
            <span>admin.sby@dejiaohui.id</span>
            <span>Cabang123!</span>
        </div>
        <div class="credential-box">
            <strong>Akun Nasional</strong>
            <span>admin@dejiaohui.id</span>
            <span>Admin123!</span>
        </div>
        <a class="btn primary full-button" href="index.php?page=login">Buka Aplikasi Cabang</a>
        <a class="btn secondary full-button" href="index.php?page=national-login">Buka Portal Nasional</a>
        <p class="warning-text">Ganti password demo dan hapus/ubah nama file <code>install.php</code> setelah pengujian.</p>
    <?php else: ?>
        <p>Installer akan membuat database pusat dan database cabang Surabaya secara terpisah di folder <code>data</code>.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <button class="btn primary full-button" type="submit">Pasang Aplikasi Pilot</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
