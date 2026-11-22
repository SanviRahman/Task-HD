<?php
require_once 'config.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $sql = file_get_contents(__DIR__.'/schema.sql');
        $pdo->exec($sql);
        $hash = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
        $st = db()->prepare("INSERT INTO users(name,username,password,role) VALUES(?,?,?,'admin') ON DUPLICATE KEY UPDATE name=VALUES(name), password=VALUES(password), role='admin'");
        $st->execute([$_POST['admin_name'], $_POST['admin_username'], $hash]);
        $message = 'ইনস্টল সম্পন্ন হয়েছে। নিরাপত্তার জন্য install.php মুছে ফেলুন, তারপর লগইন করুন।';
    } catch (Throwable $e) { $message = 'ইনস্টল করতে সমস্যা হয়েছে: '.$e->getMessage(); }
}
?><!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>JV CMS Installer</title><link rel="stylesheet" href="assets/style.css"></head><body class="auth-bg"><main class="auth-card"><div class="brand-mark">JV</div><h1>JV Construction</h1><p>প্রাথমিক সেটআপ</p><?php if($message): ?><div class="notice"><?=e($message)?></div><?php endif; ?><form method="post"><label>প্রশাসকের নাম</label><input name="admin_name" required value="System Administrator"><label>প্রশাসকের ব্যবহারকারীর নাম</label><input name="admin_username" required value="admin"><label>প্রশাসকের পাসওয়ার্ড</label><input type="password" name="admin_password" minlength="8" required><button class="btn primary">সফটওয়্যার ইনস্টল করুন</button></form><p class="muted small">ইনস্টলের আগে config.php ফাইলে ডাটাবেজের তথ্য ঠিক করুন।</p></main></body></html>
