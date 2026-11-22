<?php
require_once 'config.php';
require_login();
$pdo = db();

// Existing dashboard queries are intentionally kept unchanged.
if (is_admin()) {
    $projects = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $running = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status='running'")->fetchColumn();
    $completed = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('completed','closed')")->fetchColumn();
    $spent = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status='approved'")->fetchColumn();
    $pending = (int) $pdo->query("SELECT COUNT(*) FROM expenses WHERE status='pending'")->fetchColumn();
    $recent = $pdo->query("SELECT e.*,p.title project_title,h.name head_name,u.name user_name FROM expenses e JOIN projects p ON p.id=e.project_id JOIN expense_heads h ON h.id=e.head_id JOIN users u ON u.id=e.entered_by ORDER BY e.id DESC LIMIT 8")->fetchAll();
} else {
    $ids = $pdo->prepare("SELECT project_id FROM project_assignments WHERE user_id=? AND view_project=1");
    $ids->execute([user()['id']]);
    $allowed = array_column($ids->fetchAll(), 'project_id');
    $projects = count($allowed);
    $running = $completed = 0;
    $spent = 0;
    $pending = 0;
    $recent = [];
    if ($allowed) {
        $in = implode(',', array_map('intval', $allowed));
        $running = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status='running' AND id IN ($in)")->fetchColumn();
        $completed = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('completed','closed') AND id IN ($in)")->fetchColumn();
        $spent = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status='approved' AND project_id IN ($in)")->fetchColumn();
        $pending = (int) $pdo->query("SELECT COUNT(*) FROM expenses WHERE status='pending' AND project_id IN ($in)")->fetchColumn();
        $recent = $pdo->query("SELECT e.*,p.title project_title,h.name head_name,u.name user_name FROM expenses e JOIN projects p ON p.id=e.project_id JOIN expense_heads h ON h.id=e.head_id JOIN users u ON u.id=e.entered_by WHERE e.project_id IN ($in) ORDER BY e.id DESC LIMIT 8")->fetchAll();
    }
}

// Visualization values only. No budget or artificial transactions are assumed.
$otherProjects = max(0, $projects - $running - $completed);
$runningArc = $projects ? (int) round($running * 360 / $projects) : 0;
$completedArc = $projects ? (int) round(($running + $completed) * 360 / $projects) : 0;
include 'layout_top.php';
?>
<section class="dashboard-hero" aria-labelledby="dashboard-title">
    <div class="hero-copy">
        <span class="hero-eyebrow"><span class="hero-eyebrow-dot" aria-hidden="true"></span> JV WORKSPACE / PROJECT OVERVIEW</span>
        <h1 id="dashboard-title">প্রকল্পের প্রতিটি হিসাব,<br><em>এক জায়গায়।</em></h1>
        <p>স্বাগতম, <?= e(user()['name']) ?>। আপনার নির্মাণ প্রকল্পের বর্তমান অবস্থা ও ব্যয়ের হিসাব এক নজরে দেখুন।</p>
        <div class="hero-actions">
            <a href="expenses.php" class="action-btn action-coral"><svg class="ui-icon" aria-hidden="true"><use href="#jv-plus"></use></svg> <?= is_admin() ? 'নতুন ব্যয় যোগ করুন' : 'ব্যয় ব্যবস্থাপনা' ?></a>
            <a href="projects.php" class="action-btn action-outline-light">প্রকল্পসমূহ <svg class="ui-icon" aria-hidden="true"><use href="#jv-arrow-up-right"></use></svg></a>
        </div>
    </div>
    <div class="hero-art" aria-hidden="true">
        <svg viewBox="0 0 420 280" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g stroke="#D0E4D9" stroke-opacity=".32" stroke-width="1.4">
                <path d="M9 249h398M56 249V100l68-35 68 35v149M73 249V117h102v132M85 143h22v27H85zM120 143h22v27h-22zM85 186h22v27H85zM120 186h22v27h-22z"/>
                <path d="M207 249V59h121v190M223 84h29v30h-29zM273 84h29v30h-29zM223 130h29v30h-29zM273 130h29v30h-29zM223 177h29v30h-29zM273 177h29v30h-29zM341 249V151h49v98M365 151v-26M343 175h45M343 197h45M343 219h45"/>
                <path d="M237 58V28M206 28h95M260 28V8M302 28l-24 30M192 101l15-42M19 249l37-149M124 65V25M124 25l-37 32M124 25l37 32"/>
            </g>
            <circle cx="301" cy="40" r="67" stroke="#D0E4D9" stroke-opacity=".18"/>
            <circle cx="301" cy="40" r="104" stroke="#D0E4D9" stroke-opacity=".13"/>
        </svg>
    </div>
    <div class="hero-status" aria-label="অনুমোদনের অপেক্ষায় <?= $pending ?> টি ব্যয়">
        <div class="hero-status-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-clock"></use></svg></div>
        <div><span>অনুমোদনের অপেক্ষায়</span><strong><?= $pending ?> <small>টি ব্যয়</small></strong></div>
    </div>
</section>

<div class="section-heading dashboard-section-heading">
    <div><span class="section-eyebrow">YOUR PROJECT SNAPSHOT</span><h2>আজকের সারসংক্ষেপ</h2><p>আপনার প্রকল্পগুলোর সর্বশেষ তথ্য</p></div>
    <a href="reports.php" class="section-link">সম্পূর্ণ রিপোর্ট <svg class="ui-icon" aria-hidden="true"><use href="#jv-arrow-up-right"></use></svg></a>
</div>
<section class="dashboard-stats" aria-label="প্রকল্পের পরিসংখ্যান">
    <article class="metric-card metric-projects">
        <div class="metric-top"><span class="metric-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-building"></use></svg></span><span class="metric-kicker">01 / OVERVIEW</span></div>
        <span class="metric-label">মোট প্রকল্প</span><strong><?= number_format($projects) ?></strong><small>তৈরি / নির্ধারিত প্রকল্প</small>
    </article>
    <article class="metric-card metric-running">
        <div class="metric-top"><span class="metric-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-chart"></use></svg></span><span class="metric-kicker">02 / ONGOING</span></div>
        <span class="metric-label">চলমান প্রকল্প</span><strong><?= number_format($running) ?></strong><small>বর্তমানে কাজ চলছে</small>
    </article>
    <article class="metric-card metric-completed">
        <div class="metric-top"><span class="metric-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg></span><span class="metric-kicker">03 / FINISHED</span></div>
        <span class="metric-label">সম্পন্ন প্রকল্প</span><strong><?= number_format($completed) ?></strong><small>সম্পন্ন অথবা বন্ধ</small>
    </article>
    <article class="metric-card metric-expenses">
        <div class="metric-top"><span class="metric-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-wallet"></use></svg></span><span class="metric-kicker">04 / EXPENSES</span></div>
        <span class="metric-label">অনুমোদিত ব্যয়</span><strong class="metric-money">৳<?= number_format($spent, 2) ?></strong><small>সব অনুমোদিত ব্যয়ের মোট</small>
    </article>
</section>

<div class="dashboard-content-grid">
    <section class="dashboard-card recent-expenses" aria-labelledby="recent-expenses-title">
        <div class="dashboard-card-header"><div><span class="card-eyebrow">FINANCIAL ACTIVITY</span><h2 id="recent-expenses-title">সাম্প্রতিক ব্যয়</h2><p>সর্বশেষ <?= count($recent) ?> টি ব্যয়ের এন্ট্রি</p></div><a class="card-link" href="expenses.php">সব দেখুন <svg class="ui-icon" aria-hidden="true"><use href="#jv-arrow-up-right"></use></svg></a></div>
        <?php if ($recent): ?>
            <div class="table-wrap">
                <table class="dashboard-table"><thead><tr><th scope="col">প্রকল্প / খাত</th><th scope="col">তারিখ</th><th scope="col">এন্ট্রি করেছেন</th><th scope="col">টাকার পরিমাণ</th><th scope="col">অবস্থা</th></tr></thead><tbody>
                    <?php foreach ($recent as $r): ?>
                    <tr><td class="transaction-name" data-label="প্রকল্প"><strong><?= e($r['project_title']) ?></strong><span><?= e($r['head_name']) ?></span></td><td data-label="তারিখ"><?= e($r['entry_date']) ?></td><td data-label="এন্ট্রি করেছেন"><?= e($r['user_name']) ?></td><td class="money" data-label="পরিমাণ">৳<?= number_format((float) $r['amount'], 2) ?></td><td data-label="অবস্থা"><span class="status <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>
        <?php else: ?>
            <div class="dashboard-empty-state"><span class="empty-art"><svg class="ui-icon" aria-hidden="true"><use href="#jv-clipboard"></use></svg></span><strong>এখনো কোনো ব্যয়ের তথ্য নেই</strong><p><?= $projects > 0 ? 'প্রকল্পে ব্যয় এন্ট্রি শুরু হলে এখানে সর্বশেষ তথ্য দেখতে পাবেন।' : 'প্রথমে একটি প্রকল্প তৈরি করুন। এরপর তার ব্যয়ের হিসাব এখানে দেখতে পাবেন।' ?></p><?php if (is_admin()): ?><a class="empty-action" href="<?= $projects > 0 ? 'expenses.php' : 'projects.php' ?>"><?= $projects > 0 ? 'ব্যয় ব্যবস্থাপনায় যান' : 'প্রকল্প তৈরি করুন' ?> <svg class="ui-icon" aria-hidden="true"><use href="#jv-arrow-up-right"></use></svg></a><?php endif; ?></div>
        <?php endif; ?>
    </section>
    <div class="dashboard-side-widgets">
        <section class="dashboard-card project-status-card" aria-labelledby="project-status-title">
            <div class="dashboard-card-header"><div><span class="card-eyebrow">PROJECT HEALTH</span><h2 id="project-status-title">প্রকল্পের অবস্থা</h2></div><span class="project-status-total"><?= $projects ?> TOTAL</span></div>
            <div class="project-chart-body">
                <div class="project-donut <?= $projects === 0 ? 'is-empty' : '' ?>" style="--running-angle: <?= $runningArc ?>deg;--completed-angle: <?= $completedArc ?>deg" role="img" aria-label="মোট <?= $projects ?>, চলমান <?= $running ?>, সম্পন্ন <?= $completed ?>, অন্যান্য <?= $otherProjects ?> প্রকল্প"><div class="donut-center"><strong><?= $projects ?></strong><span>প্রকল্প</span></div></div>
                <ul class="project-legend"><li><span class="legend-mark legend-running"></span><span>চলমান</span><strong><?= $running ?></strong></li><li><span class="legend-mark legend-completed"></span><span>সম্পন্ন</span><strong><?= $completed ?></strong></li><li><span class="legend-mark legend-other"></span><span>অন্যান্য</span><strong><?= $otherProjects ?></strong></li></ul>
            </div>
        </section>
        <a class="approval-card" href="expenses.php"><span class="approval-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-clock"></use></svg></span><span class="approval-copy"><span>আপনার নজরে</span><strong><?= number_format($pending) ?> টি ব্যয় অনুমোদনের অপেক্ষায়</strong></span><svg class="ui-icon approval-arrow" aria-hidden="true"><use href="#jv-arrow-up-right"></use></svg></a>
    </div>
</div>

<section class="dashboard-shortcuts" aria-label="দ্রুত নেভিগেশন"><div class="shortcut-title"><span>QUICK ACCESS</span><h2>আপনার পরবর্তী কাজ</h2></div><div class="shortcut-links"><a href="projects.php"><svg class="ui-icon" aria-hidden="true"><use href="#jv-building"></use></svg>প্রকল্পসমূহ <svg class="ui-icon shortcut-chevron" aria-hidden="true"><use href="#jv-chevron"></use></svg></a><a href="expenses.php"><svg class="ui-icon" aria-hidden="true"><use href="#jv-receipt"></use></svg>ব্যয় ব্যবস্থাপনা <svg class="ui-icon shortcut-chevron" aria-hidden="true"><use href="#jv-chevron"></use></svg></a><a href="advances.php"><svg class="ui-icon" aria-hidden="true"><use href="#jv-wallet"></use></svg>কর্মীর অগ্রিম <svg class="ui-icon shortcut-chevron" aria-hidden="true"><use href="#jv-chevron"></use></svg></a></div></section>
<?php include 'layout_bottom.php'; ?>
