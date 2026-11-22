<?php
require_once 'config.php';require_login();$pdo=db();$projects=allowed_projects('view_project');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'add';$pid=(int)($_POST['project_id']??0);$eid=(int)($_POST['employee_id']??0);$date=(string)($_POST['advance_date']??'');$amount=$_POST['amount']??0;
 if(!can('add_expense',$pid)||!valid_date($date)||!positive_money($amount)){http_response_code(403);exit('Invalid permission, date or amount');}
 $check=$pdo->prepare("SELECT id FROM projects WHERE id=? AND status!='closed'");$check->execute([$pid]);if(!$check->fetchColumn())exit('Project is closed or invalid');
 $check=$pdo->prepare('SELECT id FROM users WHERE id=? AND active=1');$check->execute([$eid]);if(!$check->fetchColumn())exit('Invalid employee');
 $assignment=$pdo->prepare('SELECT 1 FROM project_assignments WHERE project_id=? AND user_id=?');$assignment->execute([$pid,$eid]);if(!$assignment->fetchColumn()){http_response_code(403);exit('Employee is not assigned to this project.');}
if($action==='edit'){
  $id=(int)($_POST['id']??0);$st=$pdo->prepare('SELECT * FROM employee_advances WHERE id=?');$st->execute([$id]);$old=$st->fetch();
  if(!$old||!can('edit_expense',(int)$old['project_id'])||!can('edit_expense',$pid)){http_response_code(403);exit('Edit not permitted');}
  $st=$pdo->prepare('UPDATE employee_advances SET project_id=?,employee_id=?,advance_date=?,amount=?,note=? WHERE id=?');$st->execute([$pid,$eid,$date,$amount,trim($_POST['note']??''),$id]);
 }else{$st=$pdo->prepare('INSERT INTO employee_advances(project_id,employee_id,advance_date,amount,note,entered_by) VALUES(?,?,?,?,?,?)');$st->execute([$pid,$eid,$date,$amount,trim($_POST['note']??''),user()['id']]);}
 flash('Advance saved.');redirect('advances.php');
}
$employees=$pdo->query('SELECT id,name FROM users WHERE active=1 ORDER BY name')->fetchAll();
if(is_admin())$rows=$pdo->query('SELECT a.*,p.title project_title,u.name employee_name FROM employee_advances a JOIN projects p ON p.id=a.project_id JOIN users u ON u.id=a.employee_id ORDER BY a.advance_date DESC,a.id DESC LIMIT 500')->fetchAll();
else{$st=$pdo->prepare('SELECT a.*,p.title project_title,u.name employee_name FROM employee_advances a JOIN projects p ON p.id=a.project_id JOIN users u ON u.id=a.employee_id JOIN project_assignments pa ON pa.project_id=p.id WHERE pa.user_id=? AND pa.view_project=1 ORDER BY a.advance_date DESC,a.id DESC LIMIT 500');$st->execute([user()['id']]);$rows=$st->fetchAll();}
$advanceTotal = array_reduce($rows, fn($sum, $r) => $sum + (float) $r['amount'], 0.0);
$advanceEmployeesCount = count(array_unique(array_column($rows, 'employee_id')));
include 'layout_top.php';?>
<div class="records-page records-advances" data-records-page="advances">
    <section class="records-intro" aria-labelledby="advances-heading"><div class="records-intro-copy"><span class="records-kicker"><svg class="ui-icon" aria-hidden="true"><use href="#jv-wallet"></use></svg> TEAM FINANCE <span class="records-kicker-dot"></span> 03 / ADVANCES</span><h1 id="advances-heading">কর্মীর অগ্রিম, <em>হিসাব থাকুক স্পষ্ট।</em></h1><p>কোন প্রকল্পে কোন কর্মীকে কত টাকা অগ্রিম দেওয়া হয়েছে, সব এন্ট্রি সহজে খুঁজে নিন।</p><a class="record-btn record-btn-primary records-intro-action" href="#advance-entry"><svg class="ui-icon" aria-hidden="true"><use href="#jv-plus"></use></svg> অগ্রিম যোগ করুন</a></div><div class="intro-decor intro-decor-advances" aria-hidden="true"><span class="intro-illustration"><svg class="ui-icon"><use href="#jv-wallet"></use></svg></span><span class="intro-decor-dots"></span></div></section>

    <section class="records-metrics" aria-label="অগ্রিমের সারসংক্ষেপ">
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-purple"><svg class="ui-icon" aria-hidden="true"><use href="#jv-clipboard"></use></svg></span><span>অগ্রিম এন্ট্রি</span></div><strong><?= number_format(count($rows)) ?></strong><small>তালিকায় প্রদর্শিত রেকর্ড</small></article>
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-teal"><svg class="ui-icon" aria-hidden="true"><use href="#jv-users"></use></svg></span><span>কর্মী</span></div><strong><?= number_format($advanceEmployeesCount) ?></strong><small>প্রদর্শিত এন্ট্রির স্বতন্ত্র কর্মী</small></article>
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-coral"><svg class="ui-icon" aria-hidden="true"><use href="#jv-wallet"></use></svg></span><span>মোট অগ্রিম</span></div><strong class="metric-currency">৳<?= number_format($advanceTotal, 2) ?></strong><small>প্রদর্শিত এন্ট্রির টাকার যোগফল</small></article>
    </section>
    <p class="records-scope-note">উপরের সারসংক্ষেপে সর্বশেষ সর্বোচ্চ ৫০০টি অগ্রিম এন্ট্রি গণনা করা হয়েছে। বকেয়া জানতে Employee Ledger দেখুন।</p>

    <section class="record-surface" id="advance-entry" aria-labelledby="advance-form-heading">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">01</span><div><span class="surface-kicker">EMPLOYEE PAYMENT</span><h2 id="advance-form-heading" data-form-title>নতুন অগ্রিম এন্ট্রি করুন</h2><p>নির্ধারিত প্রকল্প ও কর্মী নির্বাচন করে অগ্রিমের তথ্য দিন।</p></div></div><span class="surface-header-tag" data-form-badge>নতুন এন্ট্রি</span></div>
        <form method="post" class="record-form" id="advanceForm" data-edit-form="advance">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" id="action" value="add"><input type="hidden" name="id" id="recordId" value="">
            <div class="record-field"><label for="pid">প্রকল্প <span class="required" aria-label="required">*</span></label><select id="pid" name="project_id" required><option value="" selected disabled>প্রকল্প নির্বাচন করুন</option><?php foreach ($projects as $p): if (!can('add_expense', (int) $p['id']) || $p['status'] === 'closed') continue; ?><option value="<?= (int) $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
            <div class="record-field"><label for="eid">কর্মী <span class="required" aria-label="required">*</span></label><select name="employee_id" id="eid" required><option value="" selected disabled>কর্মী নির্বাচন করুন</option><?php foreach ($employees as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select><span class="field-hint">কর্মীটি নির্বাচিত প্রকল্পে Assign করা থাকতে হবে।</span></div>
            <div class="record-field"><label for="adate">অগ্রিমের তারিখ <span class="required" aria-label="required">*</span></label><input type="date" id="adate" name="advance_date" value="<?= date('Y-m-d') ?>" placeholder="দিন / মাস / বছর" required></div>
            <div class="record-field"><label for="amount">অগ্রিমের পরিমাণ (৳) <span class="required" aria-label="required">*</span></label><input type="number" id="amount" name="amount" step="0.01" min="0.01" max="999999999999.99" inputmode="decimal" placeholder="যেমন: 5000.00" required></div>
            <div class="record-field record-col-full"><label for="note">বিবরণ <span class="optional">(ঐচ্ছিক)</span></label><input id="note" name="note" type="text" maxlength="500" placeholder="যেমন: সাইটের নির্মাণসামগ্রী কেনার জন্য অগ্রিম" autocomplete="off"></div>
            <div class="record-form-actions"><p><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg> একই প্রকল্পে কর্মীর Advance হিসাব Ledger-এ দেখতে পারবেন।</p><div class="record-form-buttons"><button type="button" class="record-btn record-btn-secondary" id="cancelAdvanceEdit" hidden>সংশোধন বাতিল</button><button class="record-btn record-btn-primary" type="submit" id="advanceSaveButton"><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg> অগ্রিম সংরক্ষণ</button></div></div>
        </form>
    </section>

    <section class="record-surface" aria-labelledby="advance-list-heading" id="advance-list"><div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">02</span><div><span class="surface-kicker">ADVANCE HISTORY</span><h2 id="advance-list-heading">অগ্রিম লেনদেনের তালিকা</h2><p>কর্মী ও প্রকল্প অনুযায়ী অগ্রিমের ইতিহাস খুঁজে দেখুন।</p></div></div><a href="ledger.php" class="record-subtle-link">Employee Ledger <svg class="ui-icon" aria-hidden="true"><use href="#jv-arrow-up-right"></use></svg></a></div>
        <?php if ($rows): ?>
            <div class="records-toolbar" data-record-toolbar hidden><div class="record-search"><svg class="ui-icon" aria-hidden="true"><use href="#jv-grid"></use></svg><label class="sr-only" for="advanceSearch">অগ্রিম খুঁজুন</label><input type="search" id="advanceSearch" placeholder="কর্মী, প্রকল্প বা বিবরণ দিয়ে খুঁজুন..." data-search-input></div><label class="record-select-wrap"><span>প্রকল্প</span><select data-filter="project" aria-label="প্রকল্প"><option value="">সব প্রকল্প</option><?php foreach ($projects as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></label><label class="record-select-wrap"><span>কর্মী</span><select data-filter="employee" aria-label="কর্মী"><option value="">সব কর্মী</option><?php foreach ($employees as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select></label><button type="button" class="record-clear" data-clear-filters>Reset</button></div>
            <div class="records-table-wrap"><table class="records-table" data-record-table><thead><tr><th scope="col">তারিখ</th><th scope="col">প্রকল্প</th><th scope="col">কর্মী</th><th scope="col">অগ্রিমের পরিমাণ</th><th scope="col">বিবরণ</th><th scope="col">অ্যাকশন</th></tr></thead><tbody>
                <?php foreach ($rows as $r): ?>
                <tr data-record-row data-project="<?= (int) $r['project_id'] ?>" data-employee="<?= (int) $r['employee_id'] ?>">
                    <td data-label="তারিখ"><time datetime="<?= e($r['advance_date']) ?>"><?= e($r['advance_date']) ?></time></td>
                    <td data-label="প্রকল্প"><span class="record-main"><span class="table-record-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-building"></use></svg></span><span><strong><?= e($r['project_title']) ?></strong></span></span></td>
                    <td data-label="কর্মী"><span class="record-employee"><span class="employee-dot" aria-hidden="true"><svg class="ui-icon"><use href="#jv-user"></use></svg></span><?= e($r['employee_name']) ?></span></td>
                    <td data-label="অগ্রিমের পরিমাণ"><strong class="table-amount">৳<?= number_format((float) $r['amount'], 2) ?></strong></td>
                    <td data-label="বিবরণ"><span class="record-description" title="<?= e($r['note'] ?? '') ?>"><?= e($r['note'] ?: '—') ?></span></td>
                    <td data-label="অ্যাকশন"><?php if (can('edit_expense', (int) $r['project_id'])): ?><button class="record-btn record-btn-small record-btn-secondary" type="button" onclick='editAdvance(<?= e(json_encode(['id' => $r['id'], 'project_id' => $r['project_id'], 'employee_id' => $r['employee_id'], 'advance_date' => $r['advance_date'], 'amount' => $r['amount'], 'note' => $r['note']], JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)'>সংশোধন</button><?php else: ?><span class="record-no-action">—</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table></div>
            <div class="record-no-match" data-no-results hidden><svg class="ui-icon" aria-hidden="true"><use href="#jv-grid"></use></svg><strong>কোনো অগ্রিম পাওয়া যায়নি</strong><span>অন্য শব্দ দিয়ে খুঁজুন অথবা Filter Reset করুন।</span></div>
            <div class="records-pagination" data-record-pagination hidden><span data-result-count aria-live="polite"></span><div class="pager-controls"><label>প্রতি পেজে <select data-page-size aria-label="প্রতি পেজে কতটি রেকর্ড"><option value="10">10</option><option value="20">20</option><option value="50">50</option></select></label><button type="button" data-page-prev aria-label="আগের পেজ">‹</button><span data-page-info></span><button type="button" data-page-next aria-label="পরের পেজ">›</button></div></div>
        <?php else: ?><div class="record-empty"><span class="record-empty-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-wallet"></use></svg></span><strong>অগ্রিমের কোনো রেকর্ড নেই</strong><p>প্রথম অগ্রিম সংরক্ষণ করলে এখানে লেনদেনের ইতিহাস দেখতে পাবেন।</p><a href="#advance-entry" class="record-btn record-btn-secondary">অগ্রিম এন্ট্রি করুন</a></div><?php endif; ?>
    </section>
</div>
<script src="assets/records-ui.js?v=1.0.0" defer></script>
<?php include 'layout_bottom.php'; ?>
