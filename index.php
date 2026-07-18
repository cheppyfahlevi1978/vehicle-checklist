<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if (!installed()) {
    header('Location: install.php');
    exit;
}

$page = (string)($_GET['page'] ?? (tenant_user() ? 'dashboard' : 'login'));

if ($page === 'login') {
    $branch = resolve_branch();
    if (!$branch) {
        flash('danger', 'Belum ada cabang aktif.');
        redirect_to(['page' => 'select-branch']);
    }
    if (tenant_user() && (tenant_user()['branch_code'] ?? '') === $branch['code']) {
        redirect_to(['page' => 'dashboard']);
    }
    render_auth(
        'Masuk Cabang',
        $branch['name'],
        app_url(['page' => 'login-submit']),
        '<a href="' . h(app_url(['page' => 'select-branch'])) . '">Pilih cabang</a><a href="' . h(app_url(['page' => 'national-login'])) . '">Portal nasional</a>'
    );
    exit;
}

if ($page === 'login-submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $branch = resolve_branch();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    $stmt = tenant_db($branch)->prepare('SELECT * FROM users WHERE lower(email) = :email AND active = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        flash('danger', 'Email atau password tidak sesuai.');
        redirect_to(['page' => 'login']);
    }

    session_regenerate_id(true);
    $_SESSION['tenant_user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'branch_code' => $branch['code'],
    ];
    audit('LOGIN', 'Autentikasi', (string)$user['id'], 'Pengguna masuk ke aplikasi.');
    redirect_to(['page' => 'dashboard']);
}

if ($page === 'logout') {
    if (tenant_user()) {
        audit('LOGOUT', 'Autentikasi', null, 'Pengguna keluar dari aplikasi.');
    }
    unset($_SESSION['tenant_user']);
    session_regenerate_id(true);
    flash('success', 'Anda telah keluar.');
    redirect_to(['page' => 'login']);
}

if ($page === 'select-branch') {
    $branches = central_db()->query('SELECT * FROM branches WHERE active = 1 ORDER BY name')->fetchAll();
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pilih Cabang — SIYADI</title><link rel="stylesheet" href="assets/app.css"></head><body class="auth-body"><div class="auth-card"><div class="auth-logo">德</div><h1>Pilih Cabang</h1><p class="muted center">Data setiap cabang tersimpan pada database yang berbeda.</p><div class="branch-list">';
    foreach ($branches as $branch) {
        echo '<a href="' . h(app_url(['page' => 'login', 'branch' => $branch['code']])) . '"><strong>' . h($branch['name']) . '</strong><span>' . h($branch['code']) . '</span></a>';
    }
    echo '</div><div class="auth-help"><a href="' . h(app_url(['page' => 'national-login'])) . '">Portal Nasional</a></div></div></body></html>';
    exit;
}

if ($page === 'national-login') {
    if (national_user()) {
        redirect_to(['page' => 'national-dashboard']);
    }
    render_auth(
        'Portal Nasional',
        'Yayasan Dejiaohui Indonesia',
        app_url(['page' => 'national-login-submit']),
        '<a href="' . h(app_url(['page' => 'login'])) . '">Portal cabang</a>'
    );
    exit;
}

if ($page === 'national-login-submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $stmt = central_db()->prepare('SELECT * FROM national_users WHERE lower(email) = :email AND active = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        flash('danger', 'Email atau password nasional tidak sesuai.');
        redirect_to(['page' => 'national-login']);
    }

    session_regenerate_id(true);
    $_SESSION['national_user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
    redirect_to(['page' => 'national-dashboard']);
}

if ($page === 'national-logout') {
    unset($_SESSION['national_user']);
    session_regenerate_id(true);
    flash('success', 'Anda telah keluar dari portal nasional.');
    redirect_to(['page' => 'national-login']);
}

if ($page === 'dashboard') {
    require_tenant_login();
    $db = tenant_db();
    $count = static fn(string $table): int => (int)$db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
    $scalar = static fn(string $sql): float => (float)$db->query($sql)->fetchColumn();

    $stats = [
        'members' => (int)$db->query("SELECT COUNT(*) FROM members WHERE status = 'Aktif'")->fetchColumn(),
        'volunteers' => (int)$db->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Aktif'")->fetchColumn(),
        'programs' => $count('programs'),
        'beneficiaries' => $count('beneficiaries'),
        'donations' => $scalar("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status = 'Terverifikasi'"),
        'income' => $scalar("SELECT COALESCE(SUM(amount),0) FROM finances WHERE type = 'Pemasukan'"),
        'expense' => $scalar("SELECT COALESCE(SUM(amount),0) FROM finances WHERE type = 'Pengeluaran'"),
    ];
    $stats['balance'] = $stats['income'] - $stats['expense'];
    $programs = $db->query('SELECT * FROM programs ORDER BY start_date DESC, id DESC LIMIT 5')->fetchAll();
    $donations = $db->query('SELECT * FROM donations ORDER BY donation_date DESC, id DESC LIMIT 5')->fetchAll();

    ob_start(); ?>
    <div class="hero">
        <div><span class="eyebrow">Selamat datang</span><h1>Pelayanan kebajikan yang tertata, transparan, dan berdampak.</h1><p>Kelola kegiatan sosial, anggota, donasi, pendidikan moral, kebudayaan, dan laporan cabang dalam satu sistem.</p></div>
        <div class="hero-symbol">德</div>
    </div>
    <div class="stat-grid">
        <div class="stat-card"><span>Anggota Aktif</span><strong><?= number_format($stats['members']) ?></strong></div>
        <div class="stat-card"><span>Relawan Aktif</span><strong><?= number_format($stats['volunteers']) ?></strong></div>
        <div class="stat-card"><span>Program Sosial</span><strong><?= number_format($stats['programs']) ?></strong></div>
        <div class="stat-card"><span>Penerima Manfaat</span><strong><?= number_format($stats['beneficiaries']) ?></strong></div>
        <div class="stat-card"><span>Total Donasi</span><strong><?= money($stats['donations']) ?></strong></div>
        <div class="stat-card"><span>Saldo Kas</span><strong><?= money($stats['balance']) ?></strong></div>
    </div>
    <div class="two-column">
        <section class="panel"><div class="panel-head"><h2>Program Terbaru</h2><a href="<?= h(app_url(['page'=>'module','module'=>'programs'])) ?>">Lihat semua</a></div><div class="table-wrap"><table><thead><tr><th>Program</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>
        <?php if (!$programs): ?><tr><td colspan="3" class="empty">Belum ada program.</td></tr><?php endif; ?>
        <?php foreach ($programs as $row): ?><tr><td><?= h($row['name']) ?></td><td><?= h($row['start_date']) ?></td><td><span class="badge"><?= h($row['status']) ?></span></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
        <section class="panel"><div class="panel-head"><h2>Donasi Terbaru</h2><a href="<?= h(app_url(['page'=>'module','module'=>'donations'])) ?>">Lihat semua</a></div><div class="table-wrap"><table><thead><tr><th>Donatur</th><th>Tanggal</th><th>Nominal</th></tr></thead><tbody>
        <?php if (!$donations): ?><tr><td colspan="3" class="empty">Belum ada donasi.</td></tr><?php endif; ?>
        <?php foreach ($donations as $row): ?><tr><td><?= h($row['donor_name']) ?></td><td><?= h($row['donation_date']) ?></td><td><?= money($row['amount']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></section>
    </div>
    <?php render_tenant_layout('Dashboard Cabang', ob_get_clean());
    exit;
}

if ($page === 'module') {
    require_tenant_login();
    $key = (string)($_GET['module'] ?? '');
    $definition = module_definition($key);
    $db = tenant_db();
    $q = trim((string)($_GET['q'] ?? ''));
    $sql = 'SELECT * FROM ' . $definition['table'];
    $params = [];

    if ($q !== '') {
        $parts = [];
        foreach ($definition['search'] as $i => $field) {
            $parts[] = $field . ' LIKE :q' . $i;
            $params['q' . $i] = '%' . $q . '%';
        }
        $sql .= ' WHERE ' . implode(' OR ', $parts);
    }
    $sql .= ' ORDER BY id DESC LIMIT 250';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    ob_start(); ?>
    <div class="page-actions">
        <form method="get" class="search-form"><input type="hidden" name="page" value="module"><input type="hidden" name="module" value="<?= h($key) ?>"><input type="search" name="q" value="<?= h($q) ?>" placeholder="Cari data…"><button class="btn secondary" type="submit">Cari</button></form>
        <div><a class="btn secondary" href="<?= h(app_url(['page'=>'export','module'=>$key])) ?>">Ekspor CSV</a> <a class="btn primary" href="<?= h(app_url(['page'=>'form','module'=>$key])) ?>">+ Tambah <?= h($definition['singular']) ?></a></div>
    </div>
    <section class="panel"><div class="table-wrap"><table><thead><tr><th>ID</th><?php foreach ($definition['columns'] as $column): ?><th><?= h($definition['fields'][$column]['label']) ?></th><?php endforeach; ?><th>Aksi</th></tr></thead><tbody>
    <?php if (!$rows): ?><tr><td colspan="<?= count($definition['columns']) + 2 ?>" class="empty">Belum ada data.</td></tr><?php endif; ?>
    <?php foreach ($rows as $row): ?><tr><td><?= h($row['id']) ?></td><?php foreach ($definition['columns'] as $column): ?><td><?php $value=$row[$column] ?? ''; if (in_array($column, $definition['money'] ?? [], true)): ?><?= money($value) ?><?php elseif (in_array($column, ['status','verification_status','condition_status'], true)): ?><span class="badge"><?= h($value) ?></span><?php else: ?><?= h($value) ?><?php endif; ?></td><?php endforeach; ?><td class="row-actions"><a class="btn small secondary" href="<?= h(app_url(['page'=>'form','module'=>$key,'id'=>$row['id']])) ?>">Ubah</a><form method="post" action="<?= h(app_url(['page'=>'delete','module'=>$key,'id'=>$row['id']])) ?>" onsubmit="return confirm('Hapus data ini?')"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><button class="btn small danger" type="submit">Hapus</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
    <?php render_tenant_layout($definition['title'], ob_get_clean());
    exit;
}

if ($page === 'form') {
    require_tenant_login();
    $key = (string)($_GET['module'] ?? '');
    $definition = module_definition($key);
    $id = (int)($_GET['id'] ?? 0);
    $row = [];
    if ($id > 0) {
        $stmt = tenant_db()->prepare('SELECT * FROM ' . $definition['table'] . ' WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch() ?: [];
        if (!$row) {
            flash('danger', 'Data tidak ditemukan.');
            redirect_to(['page'=>'module','module'=>$key]);
        }
    }
    $old = $_SESSION['old_input'] ?? [];
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['old_input'], $_SESSION['form_errors']);

    ob_start(); ?>
    <?php if ($errors): ?><div class="alert danger"><strong>Periksa data:</strong><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="panel form-panel"><form method="post" action="<?= h(app_url(['page'=>'save','module'=>$key,'id'=>$id])) ?>"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div class="form-grid">
    <?php foreach ($definition['fields'] as $field => $meta): $value=$old[$field] ?? $row[$field] ?? ''; $type=$meta['type'] ?? 'text'; ?>
        <label class="<?= $type === 'textarea' ? 'full' : '' ?>"><?= h($meta['label']) ?><?= ($meta['required'] ?? false) ? ' *' : '' ?>
        <?php if ($type === 'textarea'): ?><textarea name="<?= h($field) ?>" rows="4"><?= h($value) ?></textarea>
        <?php elseif ($type === 'select'): ?><select name="<?= h($field) ?>"><option value="">— Pilih —</option><?php foreach ($meta['options'] as $option): ?><option value="<?= h($option) ?>" <?= (string)$value === (string)$option ? 'selected' : '' ?>><?= h($option) ?></option><?php endforeach; ?></select>
        <?php else: ?><input type="<?= h($type) ?>" name="<?= h($field) ?>" value="<?= h($value) ?>" <?= $type === 'number' ? 'step="any"' : '' ?>><?php endif; ?>
        </label>
    <?php endforeach; ?>
    </div><div class="form-actions"><a class="btn secondary" href="<?= h(app_url(['page'=>'module','module'=>$key])) ?>">Batal</a><button class="btn primary" type="submit">Simpan</button></div></form></section>
    <?php render_tenant_layout(($id ? 'Ubah ' : 'Tambah ') . $definition['singular'], ob_get_clean());
    exit;
}

if ($page === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_tenant_login();
    verify_csrf();
    $key = (string)($_GET['module'] ?? '');
    $definition = module_definition($key);
    $id = (int)($_GET['id'] ?? 0);
    [$data, $errors] = collect_module_data($definition);
    if ($errors) {
        redirect_to(['page'=>'form','module'=>$key,'id'=>$id ?: null]);
    }

    $data['updated_at'] = date('Y-m-d H:i:s');
    $db = tenant_db();
    try {
        if ($id > 0) {
            $sets = [];
            foreach (array_keys($data) as $field) {
                $sets[] = $field . ' = :' . $field;
            }
            $data['id'] = $id;
            $stmt = $db->prepare('UPDATE ' . $definition['table'] . ' SET ' . implode(', ', $sets) . ' WHERE id = :id');
            $stmt->execute($data);
            audit('UPDATE', $definition['title'], (string)$id, 'Memperbarui data.');
            flash('success', $definition['singular'] . ' berhasil diperbarui.');
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $fields = array_keys($data);
            $stmt = $db->prepare('INSERT INTO ' . $definition['table'] . ' (' . implode(',', $fields) . ') VALUES (:' . implode(',:', $fields) . ')');
            $stmt->execute($data);
            $newId = (string)$db->lastInsertId();
            audit('CREATE', $definition['title'], $newId, 'Menambahkan data.');
            flash('success', $definition['singular'] . ' berhasil ditambahkan.');
        }
    } catch (PDOException $e) {
        $_SESSION['old_input'] = $_POST;
        $_SESSION['form_errors'] = [str_contains($e->getMessage(), 'UNIQUE') ? 'Nomor/kode sudah digunakan.' : 'Data gagal disimpan: ' . $e->getMessage()];
        redirect_to(['page'=>'form','module'=>$key,'id'=>$id ?: null]);
    }
    redirect_to(['page'=>'module','module'=>$key]);
}

if ($page === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_tenant_login();
    verify_csrf();
    $key = (string)($_GET['module'] ?? '');
    $definition = module_definition($key);
    $id = (int)($_GET['id'] ?? 0);
    $stmt = tenant_db()->prepare('DELETE FROM ' . $definition['table'] . ' WHERE id = :id');
    $stmt->execute(['id' => $id]);
    audit('DELETE', $definition['title'], (string)$id, 'Menghapus data.');
    flash('success', $definition['singular'] . ' berhasil dihapus.');
    redirect_to(['page'=>'module','module'=>$key]);
}

if ($page === 'reports') {
    require_tenant_login();
    $db = tenant_db();
    $summary = [
        'donations' => (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='Terverifikasi'")->fetchColumn(),
        'distributed' => (float)$db->query('SELECT COALESCE(SUM(total_value),0) FROM distributions')->fetchColumn(),
        'income' => (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM finances WHERE type='Pemasukan'")->fetchColumn(),
        'expense' => (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM finances WHERE type='Pengeluaran'")->fetchColumn(),
        'beneficiaries' => (int)$db->query('SELECT COUNT(*) FROM beneficiaries')->fetchColumn(),
        'programs' => (int)$db->query('SELECT COUNT(*) FROM programs')->fetchColumn(),
    ];
    $summary['balance'] = $summary['income'] - $summary['expense'];
    $monthly = $db->query("SELECT substr(transaction_date,1,7) month, SUM(CASE WHEN type='Pemasukan' THEN amount ELSE 0 END) income, SUM(CASE WHEN type='Pengeluaran' THEN amount ELSE 0 END) expense FROM finances GROUP BY substr(transaction_date,1,7) ORDER BY month DESC LIMIT 12")->fetchAll();

    ob_start(); ?>
    <div class="stat-grid"><div class="stat-card"><span>Total Donasi</span><strong><?= money($summary['donations']) ?></strong></div><div class="stat-card"><span>Bantuan Tersalurkan</span><strong><?= money($summary['distributed']) ?></strong></div><div class="stat-card"><span>Pemasukan</span><strong><?= money($summary['income']) ?></strong></div><div class="stat-card"><span>Pengeluaran</span><strong><?= money($summary['expense']) ?></strong></div><div class="stat-card"><span>Saldo</span><strong><?= money($summary['balance']) ?></strong></div><div class="stat-card"><span>Penerima Manfaat</span><strong><?= number_format($summary['beneficiaries']) ?></strong></div></div>
    <section class="panel"><div class="panel-head"><h2>Rekap Keuangan Bulanan</h2></div><div class="table-wrap"><table><thead><tr><th>Bulan</th><th>Pemasukan</th><th>Pengeluaran</th><th>Selisih</th></tr></thead><tbody><?php if(!$monthly): ?><tr><td colspan="4" class="empty">Belum ada transaksi.</td></tr><?php endif; ?><?php foreach($monthly as $row): ?><tr><td><?= h($row['month']) ?></td><td><?= money($row['income']) ?></td><td><?= money($row['expense']) ?></td><td><?= money((float)$row['income']-(float)$row['expense']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <section class="panel"><div class="panel-head"><h2>Unduh Data</h2></div><div class="export-grid"><?php foreach(modules() as $key=>$module): ?><a href="<?= h(app_url(['page'=>'export','module'=>$key])) ?>"><?= $module['icon'] ?> <?= h($module['title']) ?><span>CSV</span></a><?php endforeach; ?></div></section>
    <?php render_tenant_layout('Laporan Cabang', ob_get_clean());
    exit;
}

if ($page === 'export') {
    require_tenant_login();
    $key = (string)($_GET['module'] ?? '');
    $definition = module_definition($key);
    $rows = tenant_db()->query('SELECT * FROM ' . $definition['table'] . ' ORDER BY id')->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $key . '-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_merge(['ID'], array_map(static fn($meta) => $meta['label'], $definition['fields'])), ';');
    foreach ($rows as $row) {
        $line = [$row['id']];
        foreach (array_keys($definition['fields']) as $field) {
            $line[] = $row[$field] ?? '';
        }
        fputcsv($out, $line, ';');
    }
    fclose($out);
    exit;
}

if ($page === 'national-dashboard') {
    require_national_login();
    $db = central_db();
    $branches = $db->query('SELECT * FROM branches ORDER BY name')->fetchAll();
    $active = (int)$db->query('SELECT COUNT(*) FROM branches WHERE active=1')->fetchColumn();
    $summaries = (int)$db->query('SELECT COUNT(*) FROM branch_summaries')->fetchColumn();
    $announcements = (int)$db->query('SELECT COUNT(*) FROM national_announcements')->fetchColumn();

    ob_start(); ?>
    <div class="hero"><div><span class="eyebrow">Portal Nasional</span><h1>Satu sistem nasional, data setiap cabang tetap terpisah.</h1><p>Portal pusat mengelola daftar cabang dan rekap tanpa mencampur data operasional antar-cabang.</p></div><div class="hero-symbol">善</div></div>
    <div class="stat-grid"><div class="stat-card"><span>Cabang Aktif</span><strong><?= $active ?></strong></div><div class="stat-card"><span>Pengumuman</span><strong><?= $announcements ?></strong></div><div class="stat-card"><span>Rekap Masuk</span><strong><?= $summaries ?></strong></div></div>
    <section class="panel"><div class="panel-head"><h2>Daftar Cabang</h2><a class="btn primary" href="<?= h(app_url(['page'=>'branch-form'])) ?>">+ Tambah Cabang</a></div><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Nama</th><th>Kota</th><th>Database</th><th>Status</th></tr></thead><tbody><?php foreach($branches as $branch): ?><tr><td><?= h($branch['code']) ?></td><td><?= h($branch['name']) ?></td><td><?= h($branch['city']) ?></td><td><?= h($branch['db_path']) ?></td><td><span class="badge"><?= $branch['active'] ? 'Aktif' : 'Nonaktif' ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php render_national_layout('Dashboard Nasional', ob_get_clean());
    exit;
}

if ($page === 'branches') {
    require_national_login();
    $branches = central_db()->query('SELECT * FROM branches ORDER BY name')->fetchAll();
    ob_start(); ?>
    <div class="page-actions"><div><h1>Manajemen Cabang</h1><p class="muted">Setiap cabang memakai file database terpisah.</p></div><a class="btn primary" href="<?= h(app_url(['page'=>'branch-form'])) ?>">+ Tambah Cabang</a></div>
    <section class="panel"><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Nama</th><th>Domain</th><th>Database</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($branches as $branch): ?><tr><td><?= h($branch['code']) ?></td><td><?= h($branch['name']) ?></td><td><?= h($branch['domain'] ?: 'Parameter kode') ?></td><td><?= h($branch['db_path']) ?></td><td><span class="badge"><?= $branch['active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td><a class="btn small secondary" href="<?= h(app_url(['page'=>'branch-form','id'=>$branch['id']])) ?>">Ubah</a> <a class="btn small secondary" href="<?= h(app_url(['page'=>'login','branch'=>$branch['code']])) ?>">Buka</a></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php render_national_layout('Manajemen Cabang', ob_get_clean());
    exit;
}

if ($page === 'branch-form') {
    require_national_login();
    $id = (int)($_GET['id'] ?? 0);
    $branch = [];
    if ($id) {
        $stmt = central_db()->prepare('SELECT * FROM branches WHERE id=:id');
        $stmt->execute(['id'=>$id]);
        $branch = $stmt->fetch() ?: [];
    }
    ob_start(); ?>
    <div class="page-actions"><div><h1><?= $id ? 'Ubah Cabang' : 'Tambah Cabang' ?></h1><p class="muted">Cabang baru otomatis memperoleh database SQLite sendiri.</p></div></div>
    <section class="panel form-panel"><form method="post" action="<?= h(app_url(['page'=>'branch-save','id'=>$id])) ?>"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div class="form-grid">
        <label>Kode Cabang *<input name="code" maxlength="10" value="<?= h($branch['code'] ?? '') ?>" <?= $id ? 'readonly' : '' ?> required></label>
        <label>Nama Cabang *<input name="name" value="<?= h($branch['name'] ?? '') ?>" required></label>
        <label>Kota<input name="city" value="<?= h($branch['city'] ?? '') ?>"></label>
        <label>Provinsi<input name="province" value="<?= h($branch['province'] ?? '') ?>"></label>
        <label class="full">Domain/Subdomain<input name="domain" value="<?= h($branch['domain'] ?? '') ?>" placeholder="surabaya.dejiaohui.id"></label>
        <?php if(!$id): ?><label>Nama Administrator *<input name="admin_name" required></label><label>Email Administrator *<input type="email" name="admin_email" required></label><label>Password Administrator *<input type="password" name="admin_password" minlength="8" required></label><?php endif; ?>
        <label class="checkbox full"><input type="checkbox" name="active" value="1" <?= !isset($branch['active']) || $branch['active'] ? 'checked' : '' ?>> Cabang aktif</label>
    </div><div class="form-actions"><a class="btn secondary" href="<?= h(app_url(['page'=>'branches'])) ?>">Batal</a><button class="btn primary" type="submit">Simpan Cabang</button></div></form></section>
    <?php render_national_layout($id ? 'Ubah Cabang' : 'Tambah Cabang', ob_get_clean());
    exit;
}

if ($page === 'branch-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_national_login();
    verify_csrf();
    $id = (int)($_GET['id'] ?? 0);
    $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)($_POST['code'] ?? '')));
    $name = trim((string)($_POST['name'] ?? ''));
    if ($code === '' || $name === '') {
        flash('danger', 'Kode dan nama cabang wajib diisi.');
        redirect_to(['page'=>'branch-form','id'=>$id ?: null]);
    }

    $central = central_db();
    $now = date('Y-m-d H:i:s');
    try {
        if ($id) {
            $stmt = $central->prepare('UPDATE branches SET name=:name,city=:city,province=:province,domain=:domain,active=:active,updated_at=:updated_at WHERE id=:id');
            $stmt->execute(['name'=>$name,'city'=>trim((string)($_POST['city']??'')) ?: null,'province'=>trim((string)($_POST['province']??'')) ?: null,'domain'=>strtolower(trim((string)($_POST['domain']??''))) ?: null,'active'=>isset($_POST['active']) ? 1 : 0,'updated_at'=>$now,'id'=>$id]);
            flash('success', 'Cabang berhasil diperbarui.');
        } else {
            $template = BRANCH_DIR . '/SBY.sqlite';
            $newPath = BRANCH_DIR . '/' . $code . '.sqlite';
            if (!is_file($template)) {
                throw new RuntimeException('Database template SBY tidak ditemukan.');
            }
            if (is_file($newPath)) {
                throw new RuntimeException('Database cabang dengan kode tersebut sudah ada.');
            }
            if (!copy($template, $newPath)) {
                throw new RuntimeException('Database cabang tidak dapat dibuat.');
            }

            $tenant = db_connect($newPath);
            foreach (array_keys(modules()) as $moduleKey) {
                $table = modules()[$moduleKey]['table'];
                $tenant->exec('DELETE FROM ' . $table);
                $tenant->exec("DELETE FROM sqlite_sequence WHERE name='" . $table . "'");
            }
            $tenant->exec('DELETE FROM audit_logs');
            $tenant->exec('DELETE FROM users');
            $stmt = $tenant->prepare('INSERT INTO users (name,email,password,role,active,created_at,updated_at) VALUES (?,?,?,?,1,?,?)');
            $stmt->execute([
                trim((string)$_POST['admin_name']),
                strtolower(trim((string)$_POST['admin_email'])),
                password_hash((string)$_POST['admin_password'], PASSWORD_DEFAULT),
                'Administrator Cabang',
                $now,
                $now,
            ]);

            $relativePath = 'data/branches/' . $code . '.sqlite';
            $stmt = $central->prepare('INSERT INTO branches (code,name,city,province,domain,db_path,active,created_at,updated_at) VALUES (:code,:name,:city,:province,:domain,:db_path,:active,:created_at,:updated_at)');
            $stmt->execute(['code'=>$code,'name'=>$name,'city'=>trim((string)($_POST['city']??'')) ?: null,'province'=>trim((string)($_POST['province']??'')) ?: null,'domain'=>strtolower(trim((string)($_POST['domain']??''))) ?: null,'db_path'=>$relativePath,'active'=>isset($_POST['active']) ? 1 : 0,'created_at'=>$now,'updated_at'=>$now]);
            flash('success', 'Cabang dan database terpisah berhasil dibuat.');
        }
    } catch (Throwable $e) {
        if (!$id && isset($newPath) && is_file($newPath)) {
            @unlink($newPath);
        }
        flash('danger', str_contains($e->getMessage(), 'UNIQUE') ? 'Kode atau domain cabang sudah digunakan.' : $e->getMessage());
        redirect_to(['page'=>'branch-form','id'=>$id ?: null]);
    }
    redirect_to(['page'=>'branches']);
}

http_response_code(404);
echo 'Halaman tidak ditemukan.';
