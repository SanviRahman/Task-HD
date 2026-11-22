<?php
/** Profile pictures are stored by authenticated user ID. No database migration required. */
function user_avatar_url(int $userId): string {
    if ($userId > 0) {
        foreach (['webp', 'jpg', 'png'] as $extension) {
            $name = 'user-' . $userId . '.' . $extension;
            $path = __DIR__ . '/assets/profile-photos/' . $name;
            if (is_file($path)) {
                $version = filemtime($path) ?: 1;
                return 'assets/profile-photos/' . $name . '?v=' . $version;
            }
        }
    }
    return 'assets/default-avatar.svg';
}
