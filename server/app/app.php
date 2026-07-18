<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/ui.php';

$path = request_path();
$method = request_method();

if (!is_installed() && $path !== '/install') {
    redirect('/install');
}

if ($path === '/install') {
    if ($method === 'POST') {
        verify_csrf();
        if (!is_installed()) {
            try {
                install_pilot();
                flash('success', 'Instalasi pilot berhasil. Silakan masuk menggunakan akun cabang Surabaya.');
                redirect('/login');
            } catch (Throwable $exception) {
                flash('danger', 'Instalasi gagal: ' . $exception->getMessage());
            }
        }
    }

    ?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalasi SIYADI</title><link rel="stylesheet" href="/assets/app.css?v=1.0.0"></head>
<body class="auth-body">
<div class="auth-card wide">
    <div class="auth-logo">德</div>
    <h1>Instalasi SIYADI</h1>
    <p class="muted centered">Sistem Informasi Yayasan Dejiaohui Indonesia</p>
    <?php render_flash_messages(); ?>
    <?php if (is_installed()): ?>
        <div class="alert success">Aplikasi sudah terpasang.</div>
        <a class="btn primary full-button" href="/login">Buka Portal Cabang</a>
        <a class="btn secondary full-button" href="/national/login">Buka Portal Nasional</a>
    <?php else: ?>
        <div class="install-list">
            <div><strong>Database pusat</strong><span>Daftar cabang dan akun nasional</span></div>
            <div><strong>Database cabang SBY</strong><span>Data operasional Surabaya yang terpisah</span></div>
            <div><strong>Data demo</strong><span>Anggota, program, donatur, donasi, dan kas awal</span></div>
        </div>
        <form method="post" action="/install" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Memasang…';">
            <?= csrf_field() ?>
            <button class="btn primary full-button" type="submit">Pasang Data Pilot</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
<?php
    exit;
}

if ($path === '/login') {
    $branch = current_branch();
    if (!$branch) {
        http_response_code(503);
        exit('Cabang belum tersedia. Masuk melalui portal nasional untuk membuat cabang.');
    }

    if ($method === 'POST') {
        verify_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $statement = tenant_db()->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['tenant_user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'branch_code' => $branch['code'],
            ];
            audit('LOGIN', 'Autentikasi', (string) $user['id'], 'Pengguna masuk ke aplikasi cabang.');
            redirect('/dashboard');
        }
        flash('danger', 'Email atau password tidak sesuai.');
    }

    auth_page('Masuk Cabang', $branch['name'], '/login', 'Masuk ke Cabang', true);
    exit;
}

if ($path === '/logout' && $method === 'POST') {
    verify_csrf();
    audit('LOGOUT', 'Autentikasi', null, 'Pengguna keluar dari aplikasi cabang.');
    unset($_SESSION['tenant_user']);
    session_regenerate_id(true);
    flash('success', 'Anda telah keluar.');
    redirect('/login');
}

if ($path === '/national/login') {
    if ($method === 'POST') {
        verify_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $statement = central_db()->prepare('SELECT * FROM national_users WHERE email = :email AND is_active = 1 LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['national_user'] = [
                'id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'],
            ];
            redirect('/national');
        }
        flash('danger', 'Email atau password nasional tidak sesuai.');
    }

    auth_page('Portal Nasional', 'Yayasan Dejiaohui Indonesia', '/national/login', 'Masuk ke Portal Nasional', false);
    exit;
}

if ($path === '/national/logout' && $method === 'POST') {
    verify_csrf();
    unset($_SESSION['national_user']);
    session_regenerate_id(true);
    flash('success', 'Anda telah keluar dari portal nasional.');
    redirect('/national/login');
}

if ($path === '/national' || $path === '/national/branches') {
    require_national_auth();

    if ($method === 'POST' && $path === '/national/branches') {
        verify_csrf();
        try {
            $required = ['code', 'name', 'admin_email', 'admin_password'];
            foreach ($required as $field) {
                if (trim((string) ($_POST[$field] ?? '')) === '') {
                    throw new InvalidArgumentException('Data wajib cabang belum lengkap.');
                }
            }
            provision_branch(
                (string) $_POST['code'],
                trim((string) $_POST['name']),
                trim((string) ($_POST['city'] ?? '')),
                trim((string) ($_POST['province'] ?? '')),
                trim((string) ($_POST['domain'] ?? '')) ?: null,
                strtolower(trim((string) $_POST['admin_email'])),
                (string) $_POST['admin_password']
            );
            flash('success', 'Cabang dan database terpisah berhasil dibuat.');
            redirect('/national/branches');
        } catch (Throwable $exception) {
            flash('danger', 'Cabang gagal dibuat: ' . $exception->getMessage());
        }
    }

    $branches = central_db()->query('SELECT * FROM branches ORDER BY name')->fetchAll();
    $summaryCount = (int) central_db()->query('SELECT COUNT(*) FROM branch_summaries')->fetchColumn();

    page_start($path === '/national' ? 'Dashboard Nasional' : 'Manajemen Cabang', true);
    if ($path === '/national') {
        hero('Portal nasional', 'Satu sistem nasional, database setiap cabang tetap terpisah.', 'Pusat mengelola cabang dan laporan ringkas tanpa mencampur transaksi operasional antar-cabang.', '善');
        echo '<div class="stat-grid">';
        stat_card('Cabang Aktif', (string) count(array_filter($branches, fn ($branch) => (int) $branch['is_active'] === 1)));
        stat_card('Rekap Cabang', (string) $summaryCount);
        stat_card('Model Database', 'Per Cabang');
        echo '</div>';
    }
    ?>
    <section class="panel">
        <div class="panel-head"><h2>Daftar Cabang</h2><span class="muted"><?= count($branches) ?> cabang</span></div>
        <div class="table-wrap"><table><thead><tr><th>Kode</th><th>Nama</th><th>Kota</th><th>Domain</th><th>Database</th><th>Akses</th></tr></thead><tbody>
        <?php foreach ($branches as $branch): ?>
            <tr><td><?= e($branch['code']) ?></td><td><?= e($branch['name']) ?></td><td><?= e($branch['city']) ?></td><td><?= e($branch['domain'] ?: 'Default') ?></td><td><code><?= e($branch['database_path']) ?></code></td><td><a class="btn small secondary" href="/login?branch=<?= urlencode($branch['code']) ?>">Buka</a></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </section>
    <?php if ($path === '/national/branches'): ?>
    <section class="panel form-panel">
        <div class="panel-head"><h2>Tambah Cabang Baru</h2><span class="muted">Database SQLite dibuat otomatis</span></div>
        <form method="post" action="/national/branches">
            <?= csrf_field() ?>
            <div class="form-grid">
                <label>Kode Cabang<input name="code" placeholder="SMG" maxlength="10" required></label>
                <label>Nama Cabang<input name="name" placeholder="Yayasan Dejiaohui Semarang" required></label>
                <label>Kota<input name="city" placeholder="Semarang"></label>
                <label>Provinsi<input name="province" placeholder="Jawa Tengah"></label>
                <label class="full">Domain/Subdomain Cabang<input name="domain" placeholder="semarang.dejiaohui.id (opsional)"></label>
                <label>Email Admin Cabang<input type="email" name="admin_email" placeholder="admin.smg@dejiaohui.id" required></label>
                <label>Password Awal<input type="password" name="admin_password" minlength="8" required></label>
            </div>
            <div class="form-actions"><button class="btn primary" type="submit">Buat Cabang dan Database</button></div>
        </form>
    </section>
    <?php endif;
    page_end(true);
    exit;
}

require_tenant_auth();
$db = tenant_db();

if ($path === '/' || $path === '/dashboard') {
    $stats = [
        'members' => (int) $db->query("SELECT COUNT(*) FROM members WHERE status = 'Aktif'")->fetchColumn(),
        'volunteers' => (int) $db->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Aktif'")->fetchColumn(),
        'programs' => (int) $db->query('SELECT COUNT(*) FROM social_programs')->fetchColumn(),
        'beneficiaries' => (int) $db->query('SELECT COUNT(*) FROM beneficiaries')->fetchColumn(),
        'donations' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'Terverifikasi'")->fetchColumn(),
        'income' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM finances WHERE type = 'Pemasukan'")->fetchColumn(),
        'expense' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM finances WHERE type = 'Pengeluaran'")->fetchColumn(),
    ];
    $programs = $db->query('SELECT * FROM social_programs ORDER BY start_date DESC, id DESC LIMIT 5')->fetchAll();
    $donations = $db->query('SELECT * FROM donations ORDER BY donation_date DESC, id DESC LIMIT 5')->fetchAll();

    page_start('Dashboard Cabang');
    hero('Selamat datang', 'Pelayanan kebajikan yang tertata, transparan, dan berdampak.', 'Kelola kegiatan sosial, anggota, donasi, pendidikan moral, kebudayaan, dan laporan cabang dalam satu aplikasi.', '德');
    echo '<div class="stat-grid">';
    stat_card('Anggota Aktif', number_format($stats['members']));
    stat_card('Relawan Aktif', number_format($stats['volunteers']));
    stat_card('Program Sosial', number_format($stats['programs']));
    stat_card('Penerima Manfaat', number_format($stats['beneficiaries']));
    stat_card('Total Donasi', idr($stats['donations']));
    stat_card('Saldo Kas', idr($stats['income'] - $stats['expense']));
    echo '</div><div class="two-column">';
    ?>
    <section class="panel"><div class="panel-head"><h2>Program Terbaru</h2><a href="/module/programs">Lihat semua</a></div><div class="table-wrap"><table><thead><tr><th>Program</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>
    <?php if (!$programs): ?><tr><td colspan="3" class="empty">Belum ada program.</td></tr><?php endif; ?>
    <?php foreach ($programs as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['start_date']) ?></td><td><span class="badge"><?= e($row['status']) ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
    <section class="panel"><div class="panel-head"><h2>Donasi Terbaru</h2><a href="/module/donations">Lihat semua</a></div><div class="table-wrap"><table><thead><tr><th>Donatur</th><th>Tanggal</th><th>Nominal</th></tr></thead><tbody>
    <?php if (!$donations): ?><tr><td colspan="3" class="empty">Belum ada donasi.</td></tr><?php endif; ?>
    <?php foreach ($donations as $row): ?><tr><td><?= e($row['donor_name']) ?></td><td><?= e($row['donation_date']) ?></td><td><?= e(idr($row['amount'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
    <?php
    echo '</div>';
    page_end();
    exit;
}

if ($path === '/reports') {
    $summary = [
        'donations' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE status = 'Terverifikasi'")->fetchColumn(),
        'distributed' => (float) $db->query('SELECT COALESCE(SUM(total_value), 0) FROM assistance_distributions')->fetchColumn(),
        'income' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM finances WHERE type = 'Pemasukan'")->fetchColumn(),
        'expense' => (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM finances WHERE type = 'Pengeluaran'")->fetchColumn(),
        'beneficiaries' => (int) $db->query('SELECT COUNT(*) FROM beneficiaries')->fetchColumn(),
    ];
    $monthly = $db->query("SELECT substr(transaction_date,1,7) AS month, SUM(CASE WHEN type='Pemasukan' THEN amount ELSE 0 END) AS income, SUM(CASE WHEN type='Pengeluaran' THEN amount ELSE 0 END) AS expense FROM finances GROUP BY substr(transaction_date,1,7) ORDER BY month DESC LIMIT 12")->fetchAll();
    page_start('Laporan Cabang');
    echo '<div class="stat-grid">';
    stat_card('Total Donasi', idr($summary['donations']));
    stat_card('Bantuan Tersalurkan', idr($summary['distributed']));
    stat_card('Pemasukan', idr($summary['income']));
    stat_card('Pengeluaran', idr($summary['expense']));
    stat_card('Saldo', idr($summary['income'] - $summary['expense']));
    stat_card('Penerima Manfaat', number_format($summary['beneficiaries']));
    echo '</div>';
    ?>
    <section class="panel"><div class="panel-head"><h2>Rekap Keuangan Bulanan</h2></div><div class="table-wrap"><table><thead><tr><th>Bulan</th><th>Pemasukan</th><th>Pengeluaran</th><th>Selisih</th></tr></thead><tbody>
    <?php if (!$monthly): ?><tr><td colspan="4" class="empty">Belum ada transaksi.</td></tr><?php endif; ?>
    <?php foreach ($monthly as $row): ?><tr><td><?= e($row['month']) ?></td><td><?= e(idr($row['income'])) ?></td><td><?= e(idr($row['expense'])) ?></td><td><?= e(idr((float)$row['income'] - (float)$row['expense'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
    <?php
    page_end();
    exit;
}

if (preg_match('#^/module/([a-z_]+)$#', $path, $matches)) {
    $key = $matches[1];
    $module = $config['modules'][$key] ?? null;
    if (!$module) {
        http_response_code(404); exit('Modul tidak ditemukan.');
    }

    $query = trim((string) ($_GET['q'] ?? ''));
    $sql = 'SELECT * FROM ' . $module['table'];
    $params = [];
    if ($query !== '') {
        $parts = [];
        foreach ($module['search'] as $index => $column) {
            $parameter = ':q' . $index;
            $parts[] = $column . ' LIKE ' . $parameter;
            $params[$parameter] = '%' . $query . '%';
        }
        $sql .= ' WHERE ' . implode(' OR ', $parts);
    }
    $sql .= ' ORDER BY id DESC LIMIT 200';
    $statement = $db->prepare($sql);
    $statement->execute($params);
    $rows = $statement->fetchAll();

    page_start($module['title']);
    ?>
    <div class="page-actions">
        <form class="search-form" method="get"><input type="search" name="q" value="<?= e($query) ?>" placeholder="Cari <?= e(strtolower($module['title'])) ?>…"><button class="btn secondary" type="submit">Cari</button></form>
        <a class="btn primary" href="/module/<?= e($key) ?>/new">+ Tambah Data</a>
    </div>
    <section class="panel"><div class="table-wrap"><table><thead><tr><th>ID</th><?php foreach ($module['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th>Aksi</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="<?= count($module['columns']) + 2 ?>" class="empty">Belum ada data.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?><tr><td><?= e($row['id']) ?></td>
        <?php foreach ($module['columns'] as $column => $label): ?><td><?php $value = $row[$column] ?? ''; echo in_array($column, $module['money'] ?? [], true) ? e(idr($value)) : (str_contains($column, 'status') ? '<span class="badge">' . e($value) . '</span>' : e($value)); ?></td><?php endforeach; ?>
        <td class="row-actions"><a class="btn small secondary" href="/module/<?= e($key) ?>/<?= e($row['id']) ?>/edit">Ubah</a><form method="post" action="/module/<?= e($key) ?>/<?= e($row['id']) ?>/delete" onsubmit="return confirm('Hapus data ini?')"><?= csrf_field() ?><button class="btn small danger" type="submit">Hapus</button></form></td>
    </tr><?php endforeach; ?>
    </tbody></table></div></section>
    <?php
    page_end();
    exit;
}

if (preg_match('#^/module/([a-z_]+)/(new|([0-9]+)/edit)$#', $path, $matches)) {
    $key = $matches[1];
    $module = $config['modules'][$key] ?? null;
    if (!$module) { http_response_code(404); exit('Modul tidak ditemukan.'); }
    $id = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null;
    $row = null;
    if ($id) {
        $statement = $db->prepare('SELECT * FROM ' . $module['table'] . ' WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!$row) { http_response_code(404); exit('Data tidak ditemukan.'); }
    }
    page_start(($id ? 'Ubah ' : 'Tambah ') . $module['title']);
    ?>
    <section class="panel form-panel"><form method="post" action="/module/<?= e($key) ?>/save">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($id) ?>">
        <div class="form-grid"><?php foreach ($module['fields'] as $fieldName => $field) { render_field($fieldName, $field, $row[$fieldName] ?? null); } ?></div>
        <div class="form-actions"><a class="btn secondary" href="/module/<?= e($key) ?>">Batal</a><button class="btn primary" type="submit">Simpan</button></div>
    </form></section>
    <?php
    page_end();
    exit;
}

if (preg_match('#^/module/([a-z_]+)/save$#', $path, $matches) && $method === 'POST') {
    verify_csrf();
    $key = $matches[1];
    $module = $config['modules'][$key] ?? null;
    if (!$module) { http_response_code(404); exit('Modul tidak ditemukan.'); }

    $id = (int) ($_POST['id'] ?? 0);
    $data = [];
    foreach ($module['fields'] as $fieldName => $field) {
        $value = trim((string) ($_POST[$fieldName] ?? ''));
        if (!empty($field['required']) && $value === '') {
            flash('danger', $field['label'] . ' wajib diisi.');
            redirect('/module/' . $key . ($id ? '/' . $id . '/edit' : '/new'));
        }
        $data[$fieldName] = $value === '' ? null : (($field['type'] ?? '') === 'number' ? (float) $value : $value);
    }
    $data['updated_at'] = date('Y-m-d H:i:s');

    try {
        if ($id) {
            $assignments = [];
            foreach (array_keys($data) as $column) { $assignments[] = $column . ' = :' . $column; }
            $data['id'] = $id;
            $statement = $db->prepare('UPDATE ' . $module['table'] . ' SET ' . implode(', ', $assignments) . ' WHERE id = :id');
            $statement->execute($data);
            audit('UPDATE', $module['title'], (string) $id, 'Memperbarui data.');
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $columns = array_keys($data);
            $statement = $db->prepare('INSERT INTO ' . $module['table'] . ' (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')');
            $statement->execute($data);
            $id = (int) $db->lastInsertId();
            audit('CREATE', $module['title'], (string) $id, 'Menambahkan data.');
        }
        flash('success', 'Data berhasil disimpan.');
        redirect('/module/' . $key);
    } catch (Throwable $exception) {
        flash('danger', 'Data gagal disimpan: ' . $exception->getMessage());
        redirect('/module/' . $key . ($id ? '/' . $id . '/edit' : '/new'));
    }
}

if (preg_match('#^/module/([a-z_]+)/([0-9]+)/delete$#', $path, $matches) && $method === 'POST') {
    verify_csrf();
    $key = $matches[1];
    $id = (int) $matches[2];
    $module = $config['modules'][$key] ?? null;
    if (!$module) { http_response_code(404); exit('Modul tidak ditemukan.'); }
    $statement = $db->prepare('DELETE FROM ' . $module['table'] . ' WHERE id = :id');
    $statement->execute(['id' => $id]);
    audit('DELETE', $module['title'], (string) $id, 'Menghapus data.');
    flash('success', 'Data berhasil dihapus.');
    redirect('/module/' . $key);
}

http_response_code(404);
page_start('Halaman Tidak Ditemukan');
echo '<section class="panel form-panel"><h2>404</h2><p>Halaman yang diminta tidak ditemukan.</p><a class="btn primary" href="/dashboard">Kembali ke Dashboard</a></section>';
page_end();
