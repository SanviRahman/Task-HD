<?php
require_once 'config.php';require_login();$pdo=db();
$projects=array_values(array_filter(allowed_projects('view_project'),fn($p)=>can('view_report',(int)$p['id'])));
$pid=(int)($_GET['project_id']??$_POST['project_id']??0);
$eid=(int)($_GET['employee_id']??$_POST['employee_id']??0);
$from=(string)($_GET['from']??date('Y-m-01'));
$to=(string)($_GET['to']??date('Y-m-d'));
if(!valid_date($from)||!valid_date($to)||$from>$to){http_response_code(400);exit('Invalid date range');}
if($pid && !can('view_report',$pid)){http_response_code(403);exit('Report permission required');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$date=(string)($_POST['opening_date']??'');$amount=$_POST['amount']??'';
 if(!is_admin()||!$pid||!$eid||!valid_date($date)||!is_numeric($amount)||(float)$amount<0||(float)$amount>999999999999.99){http_response_code(403);exit('Invalid opening balance request');}
 $st=$pdo->prepare('SELECT id FROM projects WHERE id=?');$st->execute([$pid]);if(!$st->fetchColumn())exit('Invalid project');
 $st=$pdo->prepare('SELECT id FROM users WHERE id=?');$st->execute([$eid]);if(!$st->fetchColumn())exit('Invalid employee');
 $st=$pdo->prepare('SELECT MIN(dt) FROM (SELECT MIN(advance_date) dt FROM employee_advances WHERE project_id=? AND employee_id=? UNION ALL SELECT MIN(entry_date) dt FROM expenses WHERE project_id=? AND advance_employee_id=?) dates');$st->execute([$pid,$eid,$pid,$eid]);$earliest=$st->fetchColumn();
 if($earliest && $date>$earliest){http_response_code(400);exit('Opening date must be on or before the first transaction.');}
 $st=$pdo->prepare('INSERT INTO employee_opening_balances(project_id,employee_id,opening_date,amount,note,updated_by) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE opening_date=VALUES(opening_date),amount=VALUES(amount),note=VALUES(note),updated_by=VALUES(updated_by)');
 $st->execute([$pid,$eid,$date,number_format((float)$amount,2,'.',''),substr(trim((string)($_POST['note']??'')),0,500),user()['id']]);flash('Opening balance saved.');redirect('ledger.php?'.http_build_query(['project_id'=>$pid,'employee_id'=>$eid,'from'=>$from,'to'=>$to]));
}
$employees=$pdo->query('SELECT id,name FROM users ORDER BY name')->fetchAll();
$opening=null;$priorAdvance=0.0;$priorExpense=0.0;$advances=0.0;$expenses=0.0;$rows=[];$closing=null;$periodOpening=null;$message='';
if($pid&&$eid){
 $s=$pdo->prepare('SELECT opening_date,amount,note FROM employee_opening_balances WHERE project_id=? AND employee_id=?');$s->execute([$pid,$eid]);$opening=$s->fetch();
 if($opening && $from<$opening['opening_date']){$message='Selected range begins before the opening balance date; select a later start date.';}
 else{
  $effective=$opening?$opening['opening_date']:'1000-01-01';
  $s=$pdo->prepare('SELECT advance_date dt, id, amount, note, "Advance" kind, 1 sort_order FROM employee_advances WHERE project_id=? AND employee_id=? AND advance_date BETWEEN ? AND ? UNION ALL SELECT entry_date dt,id,amount,description note,"Expense" kind,2 sort_order FROM expenses WHERE project_id=? AND advance_employee_id=? AND status="approved" AND entry_date BETWEEN ? AND ? ORDER BY dt,sort_order,id');
  $s->execute([$pid,$eid,$effective,$to,$pid,$eid,$effective,$to]);$all=$s->fetchAll();
  $balance=(float)($opening['amount']??0);
  foreach($all as $r){if($r['dt']<$from){$balance+=(float)$r['amount']*($r['kind']==='Advance'?1:-1);continue;} $rows[]=$r;}
  $periodOpening=$balance;
  foreach($rows as &$r){if($r['kind']==='Advance'){$advances+=(float)$r['amount'];$balance+=(float)$r['amount'];}else{$expenses+=(float)$r['amount'];$balance-=(float)$r['amount'];}$r['balance']=$balance;}unset($r);
  $closing=$balance;
 }
}
include 'layout_top.php'; ?>
<div class="records-page phase3-page finance-page records-ledger" data-records-page="ledger">
    <section class="records-intro finance-intro" aria-labelledby="ledger-heading">
        <div class="records-intro-copy">
            <span class="records-kicker"><svg class="ui-icon" aria-hidden="true"><use href="#jv-book"></use></svg> FINANCE WORKSPACE <span class="records-kicker-dot" aria-hidden="true"></span> EMPLOYEE LEDGER</span>
            <h1 id="ledger-heading">অগ্রিমের হিসাব, <em>সহজ ও স্বচ্ছ।</em></h1>
            <p>প্রকল্প ও কর্মী অনুযায়ী অগ্রিম, অনুমোদিত ব্যয় এবং প্রতিটি লেনদেনের পর বাকি টাকার হিসাব এক জায়গায় দেখুন।</p>
            <a class="record-btn record-btn-primary records-intro-action no-print" href="#ledger-filters"><svg class="ui-icon" aria-hidden="true"><use href="#jv-chart"></use></svg> হিসাব দেখুন</a>
        </div>
        <div class="intro-decor" aria-hidden="true"><span class="intro-illustration"><svg class="ui-icon"><use href="#jv-book"></use></svg></span><span class="intro-decor-dots"></span></div>
    </section>
    <?php if ($pid && $eid && !$message): ?>
        <div class="records-metrics records-metrics-four finance-metrics" aria-label="নির্বাচিত সময়ের অগ্রিম হিসাব">
            <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-purple"><svg class="ui-icon" aria-hidden="true"><use href="#jv-calendar"></use></svg></span> শুরুর ব্যালেন্স</div><strong class="metric-currency">৳<?= number_format((float)$periodOpening,2) ?></strong><small>নির্বাচিত সময়ের শুরুতে</small></article>
            <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-teal"><svg class="ui-icon" aria-hidden="true"><use href="#jv-wallet"></use></svg></span> মোট অগ্রিম</div><strong class="metric-currency">৳<?= number_format($advances,2) ?></strong><small>নির্বাচিত সময়সীমায় দেওয়া টাকা</small></article>
            <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-coral"><svg class="ui-icon" aria-hidden="true"><use href="#jv-receipt"></use></svg></span> অনুমোদিত ব্যয়</div><strong class="metric-currency">৳<?= number_format($expenses,2) ?></strong><small>অগ্রিম থেকে সমন্বয় হয়েছে</small></article>
            <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-blue"><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg></span> শেষ ব্যালেন্স</div><strong class="metric-currency<?= $closing < 0 ? ' finance-negative' : '' ?>">৳<?= number_format((float)$closing,2) ?></strong><small>ঋণাত্মক হলে ব্যয় অগ্রিম ছাড়িয়েছে</small></article>
        </div>
    <?php endif; ?>
    <section class="record-surface no-print" id="ledger-filters" aria-labelledby="ledger-filter-title">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">01</span><div><span class="surface-kicker">FIND A STATEMENT</span><h2 id="ledger-filter-title">কর্মীর লেজার দেখুন</h2><p>প্রকল্প, কর্মী এবং তারিখ বেছে নিন।</p></div></div><span class="surface-header-tag">FILTER</span></div>
        <form method="get" action="ledger.php" class="record-form">
            <div class="record-field"><label for="ledger-project">প্রকল্প <span class="required" aria-hidden="true">*</span></label><select id="ledger-project" name="project_id" required><option value="">প্রকল্প নির্বাচন করুন</option><?php foreach($projects as $p):?><option value="<?= e($p['id']) ?>" <?= $pid === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
            <div class="record-field"><label for="ledger-employee">কর্মী <span class="required" aria-hidden="true">*</span></label><select id="ledger-employee" name="employee_id" required><option value="">কর্মী নির্বাচন করুন</option><?php foreach($employees as $u):?><option value="<?= e($u['id']) ?>" <?= $eid === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
            <div class="record-field"><label for="ledger-from">শুরুর তারিখ <span class="required" aria-hidden="true">*</span></label><input id="ledger-from" type="date" name="from" value="<?= e($from) ?>" required></div>
            <div class="record-field"><label for="ledger-to">শেষ তারিখ <span class="required" aria-hidden="true">*</span></label><input id="ledger-to" type="date" name="to" value="<?= e($to) ?>" required></div>
            <div class="record-form-actions"><p><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg> শুধুমাত্র অনুমোদিত ব্যয় লেজারে সমন্বয় হয়।</p><div class="record-form-buttons"><button type="submit" class="record-btn record-btn-primary"><svg class="ui-icon" aria-hidden="true"><use href="#jv-chart"></use></svg> লেজার দেখুন</button><button type="button" class="record-btn record-btn-secondary" onclick="window.print()"><svg class="ui-icon" aria-hidden="true"><use href="#jv-receipt"></use></svg> Print / Save as PDF</button></div></div>
        </form>
    </section>
    <?php if (is_admin()): ?>
    <section class="record-surface no-print" id="ledger-opening" aria-labelledby="ledger-opening-title">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">02</span><div><span class="surface-kicker">ADMIN CONTROL</span><h2 id="ledger-opening-title">Opening Balance নির্ধারণ</h2><p>প্রথম লেনদেনের তারিখের আগে প্রাথমিক টাকা সেট করুন বা সংশোধন করুন।</p></div></div><span class="surface-header-tag">ADMIN ONLY</span></div>
        <form method="post" action="ledger.php?<?= e(http_build_query(['from'=>$from,'to'=>$to])) ?>" class="record-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <div class="record-field"><label for="opening-project">প্রকল্প <span class="required" aria-hidden="true">*</span></label><select id="opening-project" name="project_id" required><option value="">প্রকল্প নির্বাচন করুন</option><?php foreach (allowed_projects() as $p):?><option value="<?= e($p['id']) ?>" <?= $pid===(int)$p['id']?'selected':'' ?>><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
            <div class="record-field"><label for="opening-employee">কর্মী <span class="required" aria-hidden="true">*</span></label><select id="opening-employee" name="employee_id" required><option value="">কর্মী নির্বাচন করুন</option><?php foreach($employees as $u):?><option value="<?= e($u['id']) ?>" <?= $eid===(int)$u['id']?'selected':'' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
            <div class="record-field"><label for="opening-date">Opening Date <span class="required" aria-hidden="true">*</span></label><input id="opening-date" type="date" name="opening_date" value="<?= e($opening['opening_date'] ?? date('Y-m-01')) ?>" required></div>
            <div class="record-field"><label for="opening-amount">Opening Amount (৳) <span class="required" aria-hidden="true">*</span></label><input id="opening-amount" type="number" name="amount" min="0" max="999999999999.99" step="0.01" inputmode="decimal" placeholder="যেমন: 15000.00" value="<?= e($opening['amount'] ?? '0.00') ?>" required></div>
            <div class="record-field record-col-2"><label for="opening-note">নোট <span class="optional">(ঐচ্ছিক)</span></label><input id="opening-note" name="note" maxlength="500" placeholder="প্রাথমিক ব্যালেন্সের সংক্ষিপ্ত বিবরণ" value="<?= e($opening['note'] ?? '') ?>"></div>
            <div class="record-form-actions"><p><svg class="ui-icon" aria-hidden="true"><use href="#jv-clock"></use></svg> প্রথম লেনদেনের পরে Opening Date দেওয়া যাবে না।</p><div class="record-form-buttons"><button class="record-btn record-btn-primary" type="submit"><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg> ব্যালেন্স সংরক্ষণ</button></div></div>
        </form>
    </section>
    <?php endif; ?>
    <?php if ($pid && $eid): ?>
    <section class="record-surface finance-statement" aria-labelledby="ledger-result-heading">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">03</span><div><span class="surface-kicker">TRANSACTION HISTORY</span><h2 id="ledger-result-heading">লেনদেনের বিবরণ</h2><p><?= e($from) ?> থেকে <?= e($to) ?> পর্যন্ত, কর্মীভিত্তিক হিসাব।</p></div></div><span class="record-count"><?= number_format(count($rows)) ?> টি লেনদেন</span></div>
        <?php if ($message): ?><div class="finance-alert" role="alert"><?= e($message) ?></div>
        <?php else: ?>
            <div class="finance-statement-note">এই ব্যালেন্সে পূর্ববর্তী অগ্রিম ও অনুমোদিত ব্যয়ের হিসাব অন্তর্ভুক্ত। ঋণাত্মক ব্যালেন্স মানে ব্যয় অগ্রিমের চেয়ে বেশি।</div>
            <?php if ($rows): ?>
                <div class="records-toolbar no-print" data-record-toolbar hidden><label class="record-search"><svg class="ui-icon" aria-hidden="true"><use href="#jv-grid"></use></svg><input data-search-input type="search" placeholder="তারিখ, বিবরণ অথবা লেনদেনের ধরন খুঁজুন..." aria-label="লেনদেন খুঁজুন"></label><button type="button" class="record-clear" data-clear-filters>Reset</button></div>
                <div class="records-table-wrap"><table class="records-table" data-record-table><thead><tr><th scope="col">তারিখ</th><th scope="col">ধরন</th><th scope="col">বিবরণ</th><th scope="col">অগ্রিম (৳)</th><th scope="col">ব্যয় (৳)</th><th scope="col">চলতি ব্যালেন্স (৳)</th></tr></thead><tbody>
                    <?php foreach($rows as $r): ?><tr data-record-row><td data-label="তারিখ"><time datetime="<?= e($r['dt']) ?>"><?= e($r['dt']) ?></time></td><td data-label="ধরন"><span class="record-status <?= $r['kind']==='Advance'?'approved':'pending' ?>"><span class="status-dot" aria-hidden="true"></span><?= $r['kind']==='Advance'?'অগ্রিম':'ব্যয়' ?></span></td><td data-label="বিবরণ"><?= e($r['note'] ?: '—') ?></td><td data-label="অগ্রিম (৳)" class="finance-amount"><?= $r['kind']==='Advance'?number_format((float)$r['amount'],2):'—' ?></td><td data-label="ব্যয় (৳)" class="finance-amount"><?= $r['kind']==='Expense'?number_format((float)$r['amount'],2):'—' ?></td><td data-label="চলতি ব্যালেন্স (৳)" class="finance-amount <?= $r['balance']<0?'finance-negative':'' ?>"><?= number_format((float)$r['balance'],2) ?></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <div class="record-no-match" data-no-results hidden><strong>এই অনুসন্ধানে কোনো লেনদেন পাওয়া যায়নি</strong><span>Search Reset করে আবার চেষ্টা করুন।</span></div>
                <div class="records-pagination no-print" data-record-pagination hidden><span data-result-count aria-live="polite"></span><div class="pager-controls"><label>প্রতি পেজে <select data-page-size aria-label="প্রতি পেজে লেনদেন"><option value="10">10</option><option value="20">20</option><option value="50">50</option></select></label><button type="button" data-page-prev aria-label="আগের পেজ">‹</button><span data-page-info></span><button type="button" data-page-next aria-label="পরের পেজ">›</button></div></div>
            <?php else: ?><div class="record-empty"><span class="record-empty-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-book"></use></svg></span><strong>এই সময়ে কোনো লেনদেন নেই</strong><p>তারিখ পরিবর্তন করে আবার দেখুন।</p></div><?php endif; ?>
        <?php endif; ?>
    </section>
    <?php else: ?><section class="finance-helper-card"><svg class="ui-icon" aria-hidden="true"><use href="#jv-book"></use></svg><div><strong>হিসাব দেখতে প্রস্তুত?</strong><p>উপরের Filter থেকে একটি প্রকল্প ও কর্মী নির্বাচন করে “লেজার দেখুন” চাপুন।</p></div></section><?php endif; ?>
</div>
<script src="assets/records-ui.js?v=1.0.0" defer></script>
<?php include 'layout_bottom.php'; ?>
