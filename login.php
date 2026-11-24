<?php
require_once 'config.php';
if (user()) redirect('index.php');

$error = '';
$usernameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $usernameRaw = $_POST['username'] ?? '';
    $passwordRaw = $_POST['password'] ?? '';
    $usernameValue = is_string($usernameRaw) ? trim($usernameRaw) : '';
    $password = is_string($passwordRaw) ? $passwordRaw : '';
    $u = null;

    if ($usernameValue !== '' && $password !== '') {
        $st = db()->prepare("SELECT * FROM users WHERE username=? AND active=1 LIMIT 1");
        $st->execute([$usernameValue]);
        $u = $st->fetch();
    }

    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'username' => $u['username'],
            'role' => $u['role']
        ];
        require_once __DIR__ . '/phase5_lib.php';
        p5_audit_optional('auth.login', 'users', (int)$u['id'], null, 'Successful login', (int)$u['id']);
        redirect('index.php');
    }

    $error = html_entity_decode('&#2476;&#2509;&#2479;&#2476;&#2489;&#2494;&#2480;&#2453;&#2494;&#2480;&#2496;&#2480; &#2472;&#2494;&#2478; &#2438;&#2474;&#2472;&#2494;&#2480; &#2474;&#2494;&#2488;&#2451;&#2527;&#2494;&#2480;&#2509;&#2465; &#2478;&#2495;&#2482;&#2459;&#2503; &#2472;&#2494;&#2404; &#2438;&#2476;&#2494;&#2480; &#2458;&#2503;&#2487;&#2509;&#2463;&#2494; &#2453;&#2480;&#2497;&#2472;&#2404;', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#18223d">
    <title>&#2474;&#2509;&#2480;&#2476;&#2503;&#2486; | JV Construction</title>
    <link rel="icon" type="image/svg+xml" href="assets/jv-brand-mark.svg">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/brand-v1.css?v=1.0.0">
    <link rel="stylesheet" href="assets/red-actions-v1.css?v=1.0.0">
    <link rel="stylesheet" href="assets/login-v2.css?v=2.0.0">
</head>
<body class="auth-bg">
<main class="auth-layout">
    <section class="auth-visual" aria-labelledby="auth-visual-title">
        <div class="auth-visual-top">
            <img class="auth-brand-mark" src="assets/jv-brand-mark.svg" alt="JV Construction" width="56" height="56">
            <div class="auth-brand-copy">
                <strong>JV Construction</strong>
                <span>PROJECT CONTROL</span>
            </div>
            <span class="auth-top-tag">BUILT FOR BETTER CONTROL</span>
        </div>

        <div class="auth-visual-content">
            <p class="auth-eyebrow"><span></span> PROJECT OPERATIONS / MANAGEMENT</p>
            <h1 id="auth-visual-title">
                &#2474;&#2509;&#2480;&#2453;&#2482;&#2509;&#2474;&#2503;&#2480; &#2474;&#2509;&#2480;&#2468;&#2495;&#2463;&#2495; &#2489;&#2495;&#2488;&#2494;&#2476;,
                <em>&#2447;&#2454;&#2472;&#32;&#2438;&#2480;&#2451;&#32;&#2455;&#2507;&#2459;&#2494;&#2472;&#2507;&#2404;</em>
            </h1>
            <p class="auth-visual-description">
                &#2474;&#2509;&#2480;&#2453;&#2482;&#2509;&#2474;, &#2476;&#2509;&#2479;&#2527;, &#2453;&#2480;&#2509;&#2478;&#2496; &#2451; &#2478;&#2494;&#2482;&#2494;&#2478;&#2494;&#2482;&#2503;&#2480; &#2489;&#2495;&#2488;&#2494;&#2476; &#2447;&#2453; &#2460;&#2494;&#2527;&#2455;&#2494;&#2527; &#2480;&#2494;&#2454;&#2497;&#2472;&#2404;
                &#2478;&#2494;&#2464;&#2503;&#2480; &#2453;&#2494;&#2460; &#2469;&#2503;&#2453;&#2503; &#2437;&#2474;&#2494;&#2480;&#2503;&#2486;&#2472;&#2494;&#2482; &#2480;&#2495;&#2474;&#2507;&#2480;&#2509;&#2463; &#2474;&#2480;&#2509;&#2479;&#2472;&#2509;&#2468; &#2488;&#2476;&#2453;&#2495;&#2459;&#2497; &#2469;&#2494;&#2453;&#2497;&#2453; &#2488;&#2497;&#2472;&#2509;&#2470;&#2480; &#2451; &#2488;&#2497;&#2488;&#2434;&#2455;&#2468;&#2477;&#2494;&#2476;&#2503;&#2404;
            </p>

            <div class="auth-feature-list" aria-label="System features">
                <span class="auth-feature-chip">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M9 12h.01M15 12h.01"/></svg>
                    &#2474;&#2509;&#2480;&#2453;&#2482;&#2509;&#2474; &#2476;&#2509;&#2479;&#2476;&#2488;&#2509;&#2469;&#2494;&#2474;&#2472;&#2494;
                </span>
                <span class="auth-feature-chip">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5M8 17h3"/></svg>
                    &#2476;&#2509;&#2479;&#2527; &#2451; &#2482;&#2503;&#2460;&#2494;&#2480;
                </span>
                <span class="auth-feature-chip">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M6 7v13h12V7M9 7V4h6v3M9 11h6M9 15h6"/></svg>
                    &#2439;&#2472;&#2477;&#2503;&#2472;&#2509;&#2463;&#2480;&#2495; &#2451; &#2488;&#2480;&#2476;&#2480;&#2494;&#2489;
                </span>
            </div>
        </div>

        <div class="auth-visual-footer">
            <span>JV CONSTRUCTION MANAGEMENT</span>
            <span>&#2474;&#2480;&#2495;&#2453;&#2482;&#2509;&#2474;&#2472;&#2494; &#2469;&#2503;&#2453;&#2503; &#2474;&#2509;&#2480;&#2455;&#2468;&#2495;&#2404;</span>
        </div>

        <svg class="auth-blueprint" viewBox="0 0 560 430" fill="none" aria-hidden="true">
            <circle cx="365" cy="205" r="168" stroke="white" stroke-opacity=".65" stroke-width="1.2" stroke-dasharray="4 8"/>
            <circle cx="365" cy="205" r="132" stroke="white" stroke-opacity=".5" stroke-width="1.2"/>
            <path d="M52 384h462M91 384V180l130-72 128 72v204M221 108V51l-30 18M221 51l30 18M91 180h258M130 384V220h180v164M153 245h38v42h-38zM215 245h38v42h-38zM153 310h38v42h-38zM215 310h38v42h-38zM349 384V151h128v233M377 181h30v36h-30zM423 181h30v36h-30zM377 239h30v36h-30zM423 239h30v36h-30zM377 297h30v36h-30zM423 297h30v36h-30z" stroke="white" stroke-opacity=".75" stroke-width="2"/>
            <path d="M349 151 386 116h91v35M477 151l31 29v204" stroke="#ffb49f" stroke-opacity=".8" stroke-width="2"/>
            <path d="M67 384 91 180l130-72 128 72" stroke="#ffb49f" stroke-opacity=".75" stroke-width="2.4"/>
        </svg>
    </section>

    <section class="auth-main" aria-label="&#2472;&#2495;&#2480;&#2494;&#2474;&#2470; &#2482;&#2455;&#2439;&#2472;">
        <div class="auth-card-v2">
            <div class="auth-access-row">
                <span class="auth-access-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 10V7a5 5 0 0 1 10 0v3M5 10h14v11H5zM12 14v3"/></svg>
                </span>
                <span class="auth-access-copy">
                    <strong>SECURE WORKSPACE</strong>
                    <small>Authorized team access</small>
                </span>
                <span class="auth-secure-pill"><i></i> SECURE</span>
            </div>

            <header class="auth-form-heading">
                <p class="auth-form-kicker">WELCOME BACK</p>
                <h2>&#2438;&#2476;&#2494;&#2480; &#2488;&#2509;&#2476;&#2494;&#2455;&#2468;&#2478;</h2>
                <p>&#2438;&#2474;&#2472;&#2494;&#2480; &#2447;&#2453;&#2494;&#2441;&#2472;&#2509;&#2463;&#2503; &#2474;&#2509;&#2480;&#2476;&#2503;&#2486; &#2453;&#2480;&#2468;&#2503; &#2482;&#2455;&#2439;&#2472; &#2468;&#2469;&#2509;&#2479; &#2470;&#2495;&#2472;&#2404;</p>
            </header>

            <?php if ($error): ?>
                <div class="auth-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5m0 3h.01"/></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form-v2" method="post" autocomplete="on">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                <div class="auth-field-v2">
                    <label for="username">&#2476;&#2509;&#2479;&#2476;&#2489;&#2494;&#2480;&#2453;&#2494;&#2480;&#2496;&#2480; &#2472;&#2494;&#2478;</label>
                    <div class="auth-input-shell">
                        <svg class="auth-field-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-3.5 3-5.3 7-5.3s6.3 1.8 7 5.3"/></svg>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            value="<?= e($usernameValue) ?>"
                            placeholder="&#2438;&#2474;&#2472;&#2494;&#2480; &#2476;&#2509;&#2479;&#2476;&#2489;&#2494;&#2480;&#2453;&#2494;&#2480;&#2496;&#2480; &#2472;&#2494;&#2478; &#2482;&#2495;&#2454;&#2497;&#2472;"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            maxlength="60"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="auth-field-v2">
                    <label for="password">&#2474;&#2494;&#2488;&#2451;&#2527;&#2494;&#2480;&#2509;&#2465;</label>
                    <div class="auth-input-shell">
                        <svg class="auth-field-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="&#2438;&#2474;&#2472;&#2494;&#2480; &#2474;&#2494;&#2488;&#2451;&#2527;&#2494;&#2480;&#2509;&#2465; &#2470;&#2495;&#2472;"
                            autocomplete="current-password"
                            required
                        >
                        <button class="auth-password-toggle" type="button" aria-label="Show password" aria-controls="password" aria-pressed="false" data-password-toggle>
                            <svg data-eye-open viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            <svg data-eye-closed viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.2 0 9.5 6 9.5 6a16 16 0 0 1-3 3.5M6.2 6.3C3.8 8 2.5 12 2.5 12s3.3 6 9.5 6c1.3 0 2.5-.3 3.5-.7"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                </div>

                <button class="auth-submit" type="submit">
                    <span>&#2472;&#2495;&#2480;&#2494;&#2474;&#2470;&#2477;&#2494;&#2476;&#2503; &#2482;&#2455;&#2439;&#2472; &#2453;&#2480;&#2497;&#2472;</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </button>
            </form>

            <div class="auth-security-note">
                <span class="auth-security-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 19 6v5c0 4.5-2.8 8-7 10-4.2-2-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                <span><strong>&#2472;&#2495;&#2480;&#2494;&#2474;&#2470; &#2437;&#2509;&#2479;&#2494;&#2453;&#2509;&#2488;&#2503;&#2488;</strong><small>&#2486;&#2497;&#2471;&#2497;&#2478;&#2494;&#2468;&#2509;&#2480; &#2437;&#2472;&#2497;&#2478;&#2507;&#2470;&#2495;&#2468; &#2476;&#2509;&#2479;&#2476;&#2489;&#2494;&#2480;&#2453;&#2494;&#2480;&#2496;&#2480;&#2494; &#2474;&#2509;&#2480;&#2476;&#2503;&#2486; &#2453;&#2480;&#2468;&#2503; &#2474;&#2494;&#2480;&#2476;&#2503;&#2472;&#2404;</small></span>
            </div>

            <footer class="auth-card-footer">
                <span>&copy; 2026 JV Construction</span>
                <span>PROJECT CONTROL</span>
            </footer>
        </div>
    </section>
</main>
<script src="assets/login-v2.js?v=2.0.0" defer></script>
</body>
</html>
