<?php
require_once 'config.php';
require_login();
require_once __DIR__ . '/profile_photo.php';
$me = user();
$userId = (int) ($me['id'] ?? 0);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $upload = $_FILES['photo'] ?? null;
    if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'একটি ছবি নির্বাচন করুন। আপলোড না হলে আবার চেষ্টা করুন।';
    } elseif ((int) $upload['size'] < 1 || (int) $upload['size'] > 2 * 1024 * 1024) {
        $error = 'ছবির সাইজ ২ MB বা তার কম হতে হবে।';
    } elseif (!is_uploaded_file($upload['tmp_name'])) {
        $error = 'ফাইল আপলোড গ্রহণ করা যায়নি।';
    } else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
        $image = @getimagesize($upload['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$image || !isset($allowed[$mime]) || ($image['mime'] ?? '') !== $mime
            || $image[0] < 1 || $image[1] < 1 || $image[0] > 5000 || $image[1] > 5000) {
            $error = 'শুধু JPG, PNG বা WebP ছবি গ্রহণযোগ্য (সর্বোচ্চ 5000px)।';
        } else {
            $dir = __DIR__ . '/assets/profile-photos';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                $error = 'ছবি সংরক্ষণের ফোল্ডার তৈরি করা যায়নি।';
            } else {
                $extension = $allowed[$mime];
                $target = $dir . '/user-' . $userId . '.' . $extension;
                if (!move_uploaded_file($upload['tmp_name'], $target)) {
                    $error = 'ছবি সংরক্ষণ করা যায়নি। Folder permission চেক করুন।';
                } else {
                    @chmod($target, 0644);
                    foreach (['webp', 'jpg', 'png'] as $oldExtension) {
                        if ($oldExtension !== $extension) {
                            $old = $dir . '/user-' . $userId . '.' . $oldExtension;
                            if (is_file($old)) @unlink($old);
                        }
                    }
                    flash('প্রোফাইল ছবি সফলভাবে আপডেট হয়েছে।');
                    redirect('profile.php');
                }
            }
        }
    }
}
include 'layout_top.php';
?>
<section class="profile-heading"><div><span class="section-eyebrow">ACCOUNT SETTINGS</span><h1>আমার প্রোফাইল</h1><p>আপনার অ্যাকাউন্টের ছবি আপডেট করুন।</p></div></section>
<section class="profile-settings-panel" aria-labelledby="profile-photo-heading">
    <div class="profile-overview">
        <img class="profile-large-photo" src="<?= e(user_avatar_url($userId)) ?>" alt="আপনার বর্তমান প্রোফাইল ছবি" width="100" height="100">
        <div><h2 id="profile-photo-heading"><?= e((string) ($me['name'] ?? 'User')) ?></h2><p><?= e((string) ($me['username'] ?? '')) ?> · <?= e(ucwords(str_replace('_', ' ', (string) ($me['role'] ?? 'user')))) ?></p><span>Profile photo</span></div>
    </div>
    <?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="profile-upload-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="photo">নতুন ছবি নির্বাচন করুন</label>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
        <p class="profile-hint">JPG, PNG অথবা WebP · সর্বোচ্চ ২ MB। অন্যদের প্রোফাইল ছবি পরিবর্তন হবে না।</p>
        <div class="profile-actions"><button type="submit" class="btn primary">ছবি সেভ করুন</button><a href="index.php" class="btn">ড্যাশবোর্ডে ফিরুন</a></div>
    </form>
</section>
<?php include 'layout_bottom.php'; ?>
