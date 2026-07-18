<?php

declare(strict_types=1);

function central_schema(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS national_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'Pengurus Nasional',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    city TEXT,
    province TEXT,
    domain TEXT UNIQUE,
    database_path TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
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
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(branch_id, period_date),
    FOREIGN KEY(branch_id) REFERENCES branches(id) ON DELETE CASCADE
);
SQL);
}

function tenant_schema(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'Petugas Cabang',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    chinese_name TEXT,
    gender TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    address TEXT,
    join_date TEXT,
    status TEXT NOT NULL DEFAULT 'Aktif',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS donors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    donor_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    donor_type TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    address TEXT,
    status TEXT NOT NULL DEFAULT 'Aktif',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS donations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_no TEXT NOT NULL UNIQUE,
    donor_name TEXT NOT NULL,
    donation_date TEXT NOT NULL,
    amount REAL NOT NULL DEFAULT 0,
    payment_method TEXT NOT NULL,
    purpose TEXT,
    status TEXT NOT NULL DEFAULT 'Menunggu Verifikasi',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS beneficiaries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    beneficiary_no TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    identity_no TEXT,
    phone TEXT,
    address TEXT,
    economic_condition TEXT,
    need_type TEXT NOT NULL,
    family_members INTEGER,
    verification_status TEXT NOT NULL DEFAULT 'Belum Disurvei',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS social_programs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    program_code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    start_date TEXT NOT NULL,
    end_date TEXT,
    location TEXT,
    target_beneficiaries INTEGER,
    budget REAL,
    status TEXT NOT NULL DEFAULT 'Direncanakan',
    description TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS assistance_distributions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    distribution_no TEXT NOT NULL UNIQUE,
    distribution_date TEXT NOT NULL,
    program_name TEXT NOT NULL,
    beneficiary_name TEXT NOT NULL,
    assistance_type TEXT NOT NULL,
    quantity REAL,
    total_value REAL NOT NULL DEFAULT 0,
    location TEXT,
    officer_name TEXT,
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS finances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_no TEXT NOT NULL UNIQUE,
    transaction_date TEXT NOT NULL,
    type TEXT NOT NULL,
    category TEXT NOT NULL,
    description TEXT NOT NULL,
    amount REAL NOT NULL DEFAULT 0,
    payment_method TEXT,
    reference_no TEXT,
    status TEXT NOT NULL DEFAULT 'Draft',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
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
    status TEXT NOT NULL DEFAULT 'Aktif',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS moral_classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_code TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    class_date TEXT NOT NULL,
    teacher_name TEXT,
    age_group TEXT,
    participant_count INTEGER,
    location TEXT,
    status TEXT NOT NULL DEFAULT 'Direncanakan',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS cultural_activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_code TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    activity_date TEXT NOT NULL,
    category TEXT NOT NULL,
    location TEXT,
    participant_count INTEGER,
    status TEXT NOT NULL DEFAULT 'Direncanakan',
    description TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    category TEXT,
    stock REAL NOT NULL DEFAULT 0,
    unit TEXT,
    location TEXT,
    condition_status TEXT NOT NULL DEFAULT 'Baik',
    expiry_date TEXT,
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_no TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    document_type TEXT NOT NULL,
    document_date TEXT,
    expiry_date TEXT,
    file_reference TEXT,
    status TEXT NOT NULL DEFAULT 'Aktif',
    notes TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    user_name TEXT,
    action TEXT NOT NULL,
    module TEXT NOT NULL,
    record_id TEXT,
    description TEXT,
    ip_address TEXT,
    created_at TEXT NOT NULL
);
SQL);
}

function provision_branch(string $code, string $name, string $city, string $province, ?string $domain, string $adminEmail, string $adminPassword): array
{
    ensure_directories();
    $code = strtoupper(trim($code));
    if (!preg_match('/^[A-Z0-9_-]{2,10}$/', $code)) {
        throw new InvalidArgumentException('Kode cabang hanya boleh berisi huruf, angka, garis bawah, atau tanda minus.');
    }

    $relativePath = 'database/branches/' . strtolower($code) . '.sqlite';
    $absolutePath = SERVER_ROOT . '/' . $relativePath;
    $now = date('Y-m-d H:i:s');

    $central = central_db();
    central_schema($central);
    $statement = $central->prepare(
        'INSERT INTO branches (code, name, city, province, domain, database_path, is_active, created_at, updated_at)
         VALUES (:code, :name, :city, :province, :domain, :database_path, 1, :created_at, :updated_at)'
    );
    $statement->execute([
        'code' => $code,
        'name' => $name,
        'city' => $city ?: null,
        'province' => $province ?: null,
        'domain' => $domain ?: null,
        'database_path' => $relativePath,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $tenant = sqlite($absolutePath);
    tenant_schema($tenant);
    $statement = $tenant->prepare(
        'INSERT INTO users (name, email, password, role, is_active, created_at, updated_at)
         VALUES (:name, :email, :password, :role, 1, :created_at, :updated_at)'
    );
    $statement->execute([
        'name' => 'Administrator Cabang ' . $city,
        'email' => strtolower($adminEmail),
        'password' => password_hash($adminPassword, PASSWORD_DEFAULT),
        'role' => 'Administrator Cabang',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return branch_by_code($code) ?? [];
}

function install_pilot(): void
{
    ensure_directories();
    $central = central_db();
    central_schema($central);
    $now = date('Y-m-d H:i:s');

    $statement = $central->prepare('SELECT COUNT(*) FROM national_users WHERE email = :email');
    $statement->execute(['email' => 'admin@dejiaohui.id']);
    if ((int) $statement->fetchColumn() === 0) {
        $insert = $central->prepare(
            'INSERT INTO national_users (name, email, password, role, is_active, created_at, updated_at)
             VALUES (:name, :email, :password, :role, 1, :created_at, :updated_at)'
        );
        $insert->execute([
            'name' => 'Administrator Nasional',
            'email' => 'admin@dejiaohui.id',
            'password' => password_hash('Admin123!', PASSWORD_DEFAULT),
            'role' => 'Super Admin Nasional',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    if (!branch_by_code('SBY')) {
        provision_branch(
            'SBY',
            'Yayasan Dejiaohui Surabaya',
            'Surabaya',
            'Jawa Timur',
            null,
            'admin.sby@dejiaohui.id',
            'Cabang123!'
        );
    }

    $branch = branch_by_code('SBY');
    if (!$branch) {
        throw new RuntimeException('Cabang pilot gagal dibuat.');
    }

    $tenant = tenant_db($branch);
    tenant_schema($tenant);

    if ((int) $tenant->query('SELECT COUNT(*) FROM members')->fetchColumn() === 0) {
        $tenant->beginTransaction();
        try {
            $member = $tenant->prepare(
                'INSERT INTO members (member_no, name, chinese_name, gender, phone, email, address, join_date, status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $member->execute(['SBY-AGT-000001', 'Budi Dharmawan', null, 'Laki-laki', '081234567890', 'budi@example.test', 'Surabaya', date('Y-m-d', strtotime('-3 years')), 'Aktif', 'Data contoh; silakan dihapus.', $now, $now]);
            $member->execute(['SBY-AGT-000002', 'Maya Lestari', null, 'Perempuan', '081298765432', 'maya@example.test', 'Sidoarjo', date('Y-m-d', strtotime('-1 year')), 'Aktif', 'Data contoh; silakan dihapus.', $now, $now]);

            $tenant->prepare(
                'INSERT INTO social_programs (program_code, name, category, start_date, end_date, location, target_beneficiaries, budget, status, description, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute(['SBY-KGT-' . date('Y') . '-000001', 'Bakti Sosial Sembako', 'Pembagian Sembako', date('Y-m-d', strtotime('+14 days')), date('Y-m-d', strtotime('+14 days')), 'Surabaya', 100, 25000000, 'Direncanakan', 'Program contoh untuk uji coba cabang.', $now, $now]);

            $tenant->prepare(
                'INSERT INTO donors (donor_no, name, donor_type, phone, email, address, status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute(['SBY-DTR-000001', 'Donatur Contoh', 'Perorangan', '081200000001', 'donatur@example.test', 'Surabaya', 'Aktif', 'Data contoh.', $now, $now]);

            $tenant->prepare(
                'INSERT INTO donations (receipt_no, donor_name, donation_date, amount, payment_method, purpose, status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute(['SBY-KWT-' . date('Y') . '-000001', 'Donatur Contoh', date('Y-m-d'), 5000000, 'Transfer Bank', 'Bakti Sosial', 'Terverifikasi', 'Data contoh.', $now, $now]);

            $tenant->prepare(
                'INSERT INTO finances (transaction_no, transaction_date, type, category, description, amount, payment_method, reference_no, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute(['SBY-KAS-' . date('Y') . '-000001', date('Y-m-d'), 'Pemasukan', 'Donasi', 'Donasi awal program', 5000000, 'Transfer Bank', 'SBY-KWT-' . date('Y') . '-000001', 'Disetujui', $now, $now]);

            $tenant->commit();
        } catch (Throwable $exception) {
            $tenant->rollBack();
            throw $exception;
        }
    }
}
