<?php
require_once 'config.php';
if (user()) redirect('index.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 $st=db()->prepare("SELECT * FROM users WHERE username=? AND active=1 LIMIT 1"); $st->execute([trim($_POST['username'])]); $u=$st->fetch();
 if($u && password_verify($_POST['password'],$u['password'])) { session_regenerate_id(true); $_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'username'=>$u['username'],'role'=>$u['role']]; require_once __DIR__.'/phase5_lib.php'; p5_audit_optional('auth.login','users',(int)$u['id'],null,'Successful login',(int)$u['id']); redirect('index.php'); }
 $error='ব্যবহারকারীর নাম অথবা পাসওয়ার্ড সঠিক নয়।';
}
?><!doctype html><html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>প্রবেশ | JV Construction</title><link rel="icon" type="image/svg+xml" href="assets/jv-brand-mark.svg"><link rel="stylesheet" href="assets/style.css"><link rel="stylesheet" href="assets/brand-v1.css?v=1.0.0"><link rel="stylesheet" href="assets/red-actions-v1.css?v=1.0.0"></head><body class="auth-bg"><main class="auth-card"><div class="brand-mark jv-auth-brand"><img src="assets/jv-brand-mark.svg" alt="JV Construction logo" width="62" height="62"></div><h1>JV Construction</h1><p>ব্যবস্থাপনা সফটওয়্যার</p><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>ব্যবহারকারীর নাম</label><input name="username" required autofocus><label>পাসওয়ার্ড</label><input type="password" name="password" required><button class="btn primary">লগইন করুন</button></form><p class="muted small">নিরাপদ প্রকল্প ও ব্যয় ব্যবস্থাপনা</p></main></body></html>
