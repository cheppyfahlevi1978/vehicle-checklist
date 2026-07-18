<?php

declare(strict_types=1);

function render_flash_messages(): void
{
    foreach (pull_flash() as $item) {
        echo '<div class="alert ' . e($item['type']) . '">' . e($item['message']) . '</div>';
    }
}

function page_start(string $title, bool $national = false): void
{
    global $config;
    $user = $national ? national_user() : tenant_user();
    $branch = $national ? null : current_branch();
    $path = request_path();
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#741f2f">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> — <?= e($config['name']) ?></title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="/assets/app.css?v=1.0.0">
</head>
<body>
<?php if ($national): ?>
<div class="national-shell">
    <header class="national-header">
        <a class="brand" href="/national"><span class="brand-mark">德</span><span><strong>Portal Nasional</strong><small>Yayasan Dejiaohui Indonesia</small></span></a>
        <nav>
            <a href="/national">Dashboard</a>
            <a href="/national/branches">Cabang</a>
            <form method="post" action="/national/logout"><?= csrf_field() ?><button class="link-button" type="submit">Keluar</button></form>
        </nav>
    </header>
    <main class="national-main">
        <div class="page-heading"><div><h1><?= e($title) ?></h1><p><?= e($user['name'] ?? '') ?> · <?= e($user['role'] ?? '') ?></p></div></div>
        <?php render_flash_messages(); ?>
<?php else: ?>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="/dashboard"><span class="brand-mark">德</span><span><strong><?= e($config['name']) ?></strong><small>Dejiaohui Indonesia</small></span></a>
        <div class="branch-card"><small>Cabang aktif</small><strong><?= e($branch['name'] ?? 'Cabang') ?></strong><span><?= e($branch['code'] ?? '') ?></span></div>
        <nav>
            <a class="<?= $path === '/dashboard' ? 'active' : '' ?>" href="/dashboard">🏠 Dashboard</a>
            <?php foreach ($config['modules'] as $key => $module): ?>
                <a class="<?= str_starts_with($path, '/module/' . $key) ? 'active' : '' ?>" href="/module/<?= e($key) ?>"><?= $module['icon'] ?> <?= e($module['title']) ?></a>
            <?php endforeach; ?>
            <a class="<?= $path === '/reports' ? 'active' : '' ?>" href="/reports">📊 Laporan</a>
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <button class="menu-toggle" type="button" onclick="document.body.classList.toggle('menu-open')">☰</button>
            <div><strong><?= e($title) ?></strong><small><?= e(date('d F Y')) ?></small></div>
            <div class="user-box"><span><?= e($user['name'] ?? '') ?></span><small><?= e($user['role'] ?? '') ?></small><form method="post" action="/logout"><?= csrf_field() ?><button class="link-button" type="submit">Keluar</button></form></div>
        </header>
        <section class="content">
            <?php render_flash_messages(); ?>
<?php endif;
}

function page_end(bool $national = false): void
{
    ?>
    </main>
</div>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
</script>
</body>
</html>
<?php
}

function auth_page(string $title, string $subtitle, string $action, string $button, bool $showNationalLink = false): void
{
    global $config;
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#741f2f">
    <title><?= e($title) ?> — <?= e($config['name']) ?></title><link rel="stylesheet" href="/assets/app.css?v=1.0.0">
</head>
<body class="auth-body">
<div class="auth-card">
    <div class="auth-logo">德</div>
    <h1><?= e($title) ?></h1>
    <p class="muted centered"><?= e($subtitle) ?></p>
    <?php render_flash_messages(); ?>
    <form method="post" action="<?= e($action) ?>" class="form-stack">
        <?= csrf_field() ?>
        <label>Email<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus></label>
        <label>Password<input type="password" name="password" required></label>
        <button class="btn primary" type="submit"><?= e($button) ?></button>
    </form>
    <div class="auth-help">
        <?php if ($showNationalLink): ?><a href="/national/login">Portal Nasional</a><?php else: ?><a href="/login">Portal Cabang</a><?php endif; ?>
    </div>
</div>
</body>
</html>
<?php
}

function render_field(string $name, array $field, mixed $value = null): void
{
    $type = $field['type'] ?? 'text';
    $required = !empty($field['required']) ? ' required' : '';
    $full = $type === 'textarea' ? ' full' : '';
    echo '<label class="' . $full . '">' . e($field['label']);

    if ($type === 'textarea') {
        echo '<textarea name="' . e($name) . '" rows="4"' . $required . '>' . e($value) . '</textarea>';
    } elseif ($type === 'select') {
        echo '<select name="' . e($name) . '"' . $required . '><option value="">— Pilih —</option>';
        foreach ($field['options'] ?? [] as $option) {
            $selected = (string) $value === (string) $option ? ' selected' : '';
            echo '<option value="' . e($option) . '"' . $selected . '>' . e($option) . '</option>';
        }
        echo '</select>';
    } else {
        $step = $type === 'number' ? ' step="any"' : '';
        echo '<input type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '"' . $step . $required . '>';
    }
    echo '</label>';
}

function hero(string $eyebrow, string $title, string $description, string $symbol = '德'): void
{
    echo '<section class="hero"><div><span class="eyebrow">' . e($eyebrow) . '</span><h2>' . e($title) . '</h2><p>' . e($description) . '</p></div><div class="hero-symbol">' . e($symbol) . '</div></section>';
}

function stat_card(string $label, string $value): void
{
    echo '<div class="stat-card"><span>' . e($label) . '</span><strong>' . e($value) . '</strong></div>';
}
