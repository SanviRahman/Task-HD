<?php
require_once 'config.php'; require_login(); $pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST' && is_admin()){verify_csrf();$st=$pdo->prepare("INSERT INTO projects(financial_year_id,title,tender_id,project_head,start_date,duration_days) VALUES(?,?,?,?,?,?)");$st->execute([$_POST['financial_year_id'],trim($_POST['title']),trim($_POST['tender_id']),trim($_POST['project_head']),$_POST['start_date'],max(1,(int)$_POST['duration_days'])]);flash('Project created. Assign users and permissions from Users & permissions.');redirect('projects.php');}
if(is_admin())$projects=$pdo->query("SELECT p.*,f.name fy FROM projects p JOIN financial_years f ON f.id=p.financial_year_id ORDER BY p.id DESC")->fetchAll();else{$st=$pdo->prepare("SELECT p.*,f.name fy FROM projects p JOIN financial_years f ON f.id=p.financial_year_id JOIN project_assignments a ON a.project_id=p.id WHERE a.user_id=? AND a.view_project=1 ORDER BY p.id DESC");$st->execute([user()['id']]);$projects=$st->fetchAll();}
$years=$pdo->query("SELECT * FROM financial_years ORDER BY start_date DESC")->fetchAll();$projectCount = count($projects);
$projectRunning = count(array_filter($projects, fn($p) => $p['status'] === 'running'));
$projectCompleted = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));
$projectStatusLabels = ['running' => 'চলমান', 'completed' => 'সম্পন্ন', 'closed' => 'বন্ধ'];
include 'layout_top.php';?>
<div class="records-page records-projects" data-records-page="projects">
    <section class="records-intro" aria-labelledby="projects-heading">
        <div class="records-intro-copy">
            <span class="records-kicker"><svg class="ui-icon" aria-hidden="true"><use href="#jv-building"></use></svg> PROJECT WORKSPACE <span class="records-kicker-dot"></span> 01 / PROJECTS</span>
            <h1 id="projects-heading">প্রকল্প পরিচালনা, <em>আরও সহজে।</em></h1>
            <p>চলমান কাজ, প্রকল্পের সময়সীমা এবং দরপত্রের তথ্য—সবকিছু গুছিয়ে এক জায়গায় রাখুন।</p>
            <?php if (is_admin()): ?><a class="record-btn record-btn-primary records-intro-action" href="#new-project"><svg class="ui-icon" aria-hidden="true"><use href="#jv-plus"></use></svg> নতুন প্রকল্প</a><?php endif; ?>
        </div>
        <div class="intro-decor intro-decor-projects" aria-hidden="true"><span class="intro-illustration"><svg class="ui-icon"><use href="#jv-building"></use></svg></span><span class="intro-decor-dots"></span></div>
    </section>

    <section class="records-metrics" aria-label="প্রকল্পের সারসংক্ষেপ">
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-purple"><svg class="ui-icon" aria-hidden="true"><use href="#jv-building"></use></svg></span><span>সব প্রকল্প</span></div><strong><?= number_format($projectCount) ?></strong><small>আপনার দেখা যায় এমন মোট প্রকল্প</small></article>
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-teal"><svg class="ui-icon" aria-hidden="true"><use href="#jv-chart"></use></svg></span><span>চলমান</span></div><strong><?= number_format($projectRunning) ?></strong><small>বর্তমানে কাজ চলমান রয়েছে</small></article>
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-coral"><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg></span><span>সম্পন্ন</span></div><strong><?= number_format($projectCompleted) ?></strong><small>কাজ শেষ হওয়া প্রকল্প</small></article>
    </section>

    <?php if (is_admin()): ?>
    <section class="record-surface" id="new-project" aria-labelledby="project-form-heading">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">01</span><div><span class="surface-kicker">PROJECT DETAILS</span><h2 id="project-form-heading">নতুন প্রকল্প তৈরি করুন</h2><p>নিচের তথ্যগুলো পূরণ করে আপনার নতুন প্রকল্পটি যোগ করুন।</p></div></div><span class="surface-header-tag">Admin access</span></div>
        <?php if (!$years): ?>
            <div class="record-callout" role="status"><svg class="ui-icon" aria-hidden="true"><use href="#jv-calendar"></use></svg><span>প্রকল্প শুরুর আগে একটি অর্থবছর তৈরি করতে হবে। <a href="years.php">অর্থবছর তৈরি করুন</a></span></div>
        <?php else: ?>
            <form method="post" class="record-form" id="projectForm">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <div class="record-field record-col-2"><label for="projectTitle">প্রকল্পের নাম <span class="required" aria-label="required">*</span></label><input id="projectTitle" name="title" type="text" maxlength="180" placeholder="যেমন: শহর উন্নয়ন ও সড়ক নির্মাণ প্রকল্প" autocomplete="off" required><span class="field-hint">প্রকল্পটি সহজে চেনা যায় এমন নাম দিন।</span></div>
                <div class="record-field"><label for="projectTender">দরপত্র আইডি <span class="required" aria-label="required">*</span></label><input id="projectTender" name="tender_id" type="text" maxlength="100" placeholder="যেমন: JV-2026-001" autocomplete="off" required></div>
                <div class="record-field"><label for="projectYear">অর্থবছর <span class="required" aria-label="required">*</span></label><select id="projectYear" name="financial_year_id" required><option value="" selected disabled>অর্থবছর নির্বাচন করুন</option><?php foreach ($years as $y): ?><option value="<?= (int) $y['id'] ?>"><?= e($y['name']) ?></option><?php endforeach; ?></select></div>
                <div class="record-field"><label for="projectHead">প্রকল্প প্রধান <span class="optional">(ঐচ্ছিক)</span></label><input id="projectHead" name="project_head" type="text" maxlength="120" placeholder="যেমন: প্রকৌশলী মো. রহমান" autocomplete="off"></div>
                <div class="record-field"><label for="projectStart">শুরুর তারিখ <span class="required" aria-label="required">*</span></label><input id="projectStart" type="date" name="start_date" placeholder="দিন / মাস / বছর" required></div>
                <div class="record-field"><label for="projectDuration">মেয়াদ (দিন) <span class="required" aria-label="required">*</span></label><input id="projectDuration" type="number" min="1" step="1" name="duration_days" value="30" placeholder="যেমন: ১৮০" inputmode="numeric" required></div>
                <div class="record-form-actions"><p><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg> তথ্যগুলো যাচাই করে সংরক্ষণ করুন।</p><button class="record-btn record-btn-primary" type="submit"><svg class="ui-icon" aria-hidden="true"><use href="#jv-plus"></use></svg> প্রকল্প তৈরি করুন</button></div>
            </form>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="record-surface" aria-labelledby="project-list-heading" id="project-list">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">02</span><div><span class="surface-kicker">PROJECT DIRECTORY</span><h2 id="project-list-heading">প্রকল্পের তালিকা</h2><p>প্রকল্প খুঁজুন এবং তার বর্তমান অবস্থা দেখুন।</p></div></div><span class="record-count"><?= number_format($projectCount) ?> প্রকল্প</span></div>
        <?php if ($projects): ?>
            <div class="records-toolbar" data-record-toolbar hidden>
                <div class="record-search"><svg class="ui-icon" aria-hidden="true"><use href="#jv-grid"></use></svg><label class="sr-only" for="projectSearch">প্রকল্প খুঁজুন</label><input type="search" id="projectSearch" placeholder="প্রকল্পের নাম, দরপত্র বা প্রধান দিয়ে খুঁজুন..." data-search-input></div>
                <label class="record-select-wrap"><span>অবস্থা</span><select aria-label="প্রকল্পের অবস্থা" data-filter="status"><option value="">সব অবস্থা</option><option value="running">চলমান</option><option value="completed">সম্পন্ন</option><option value="closed">বন্ধ</option></select></label>
                <label class="record-select-wrap"><span>অর্থবছর</span><select aria-label="অর্থবছর" data-filter="year"><option value="">সব অর্থবছর</option><?php foreach ($years as $y): ?><option value="<?= (int) $y['id'] ?>"><?= e($y['name']) ?></option><?php endforeach; ?></select></label>
                <button class="record-clear" type="button" data-clear-filters>Reset</button>
            </div>
            <div class="records-table-wrap"><table class="records-table" data-record-table><thead><tr><th scope="col">প্রকল্প</th><th scope="col">দরপত্র</th><th scope="col">অর্থবছর</th><th scope="col">শুরুর তারিখ</th><th scope="col">মেয়াদ</th><th scope="col">অবস্থা</th></tr></thead><tbody>
                <?php foreach ($projects as $p): ?>
                <tr data-record-row data-year="<?= (int) $p['financial_year_id'] ?>" data-status="<?= e($p['status']) ?>">
                    <td data-label="প্রকল্প"><span class="record-main"><span class="table-record-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-building"></use></svg></span><span><strong><?= e($p['title']) ?></strong><small><?= e($p['project_head'] ?: 'প্রধান নির্ধারিত নেই') ?></small></span></span></td>
                    <td data-label="দরপত্র"><span class="record-code"><?= e($p['tender_id']) ?></span></td>
                    <td data-label="অর্থবছর"><?= e($p['fy']) ?></td>
                    <td data-label="শুরুর তারিখ"><time datetime="<?= e($p['start_date']) ?>"><?= e($p['start_date']) ?></time></td>
                    <td data-label="মেয়াদ"><?= (int) $p['duration_days'] ?> দিন</td>
                    <td data-label="অবস্থা"><span class="record-status <?= e($p['status']) ?>"><span class="status-dot" aria-hidden="true"></span><?= e($projectStatusLabels[$p['status']] ?? ucfirst($p['status'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table></div>
            <div class="record-no-match" data-no-results hidden><svg class="ui-icon" aria-hidden="true"><use href="#jv-grid"></use></svg><strong>কোনো মিল পাওয়া যায়নি</strong><span>অন্য নাম দিয়ে খুঁজুন অথবা Filter Reset করুন।</span></div>
            <div class="records-pagination" data-record-pagination hidden><span data-result-count aria-live="polite"></span><div class="pager-controls"><label>প্রতি পেজে <select data-page-size aria-label="প্রতি পেজে কতটি রেকর্ড"><option value="10">10</option><option value="20">20</option><option value="50">50</option></select></label><button type="button" data-page-prev aria-label="আগের পেজ">‹</button><span data-page-info></span><button type="button" data-page-next aria-label="পরের পেজ">›</button></div></div>
        <?php else: ?>
            <div class="record-empty"><span class="record-empty-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-building"></use></svg></span><strong>এখনো কোনো প্রকল্প নেই</strong><p><?= is_admin() ? 'উপরের ফর্ম ব্যবহার করে প্রথম প্রকল্প তৈরি করুন।' : 'আপনার জন্য কোনো প্রকল্প নির্ধারণ করা হয়নি। প্রয়োজনে Admin-এর সঙ্গে যোগাযোগ করুন।' ?></p><?php if (is_admin()): ?><a href="#new-project" class="record-btn record-btn-secondary">নতুন প্রকল্প যোগ করুন</a><?php endif; ?></div>
        <?php endif; ?>
    </section>
</div>
<script src="assets/records-ui.js?v=1.0.0" defer></script>
<?php include 'layout_bottom.php'; ?>
