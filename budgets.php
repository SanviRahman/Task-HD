<?php
require_once __DIR__.'/phase5_lib.php';
require_login(); p5_require_ready();
$pdo=db();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    p5_admin(); verify_csrf();
    $pid=(int)($_POST['project_id']??0);
    $amount=p5_money($_POST['budget_amount']??'',true);
    $notes=p5_get_string('notes',500);
    p5_project($pid);
    if ($amount===null) p5_bad_request('সঠিক বাজেট লিখুন (০ বা তার বেশি)।');
    try {
        $pdo->beginTransaction();
        $st=$pdo->prepare('INSERT INTO project_budgets(project_id,budget_amount,notes,updated_by) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE budget_amount=VALUES(budget_amount),notes=VALUES(notes),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP');
        $st->execute([$pid,$amount,$notes?:null,(int)user()['id']]);
        p5_audit('budget.saved','project_budgets',$pid,$pid,'Budget updated to BDT '.$amount);
        $pdo->commit();
        flash('প্রকল্পের বাজেট সংরক্ষণ হয়েছে।');redirect('budgets.php');
    } catch (Throwable $ex) { if($pdo->inTransaction())$pdo->rollBack(); throw $ex; }
}
$rows=p5_budget_rows();
$projects=allowed_projects(is_admin() ? 'view_project' : 'view_report');
$grandBudget=$grandSpent=$grandDue=0.0;
foreach ($rows as $r) { $grandBudget+=(float)$r['budget_amount'];$grandSpent+=p5_amount_spent($r);$grandDue+=(float)$r['purchase_amount']-(float)$r['supplier_paid']; }
include 'layout_top.php';
?>
<div class="p5-page" data-p5-page="budgets">
 <section class="p5-hero"><div class="p5-hero-inner"><span class="p5-eyebrow">PROJECT FINANCE / 01</span><h1>প্রকল্পের বাজেট, <em>পরিষ্কার হিসাব।</em></h1><p>প্রতিটি প্রকল্পে অনুমোদিত ব্যয় এবং সরবরাহকারীর ক্রয়ের হিসাব দেখে সময়মতো সিদ্ধান্ত নিন।</p></div><span class="p5-hero-art" aria-hidden="true">৳</span></section>
 <section class="p5-kpis" aria-label="বাজেট সারসংক্ষেপ">
  <article class="p5-kpi"><span class="p5-kpi-label">মোট নির্ধারিত বাজেট</span><strong><?= p5_cash_view($grandBudget) ?></strong><small><?= count($rows) ?>টি দৃশ্যমান প্রকল্প</small></article>
  <article class="p5-kpi"><span class="p5-kpi-label">মোট হিসাবভুক্ত খরচ</span><strong><?= p5_cash_view($grandSpent) ?></strong><small>অনুমোদিত ব্যয় + সরবরাহকারী ক্রয়</small></article>
  <article class="p5-kpi"><span class="p5-kpi-label">বাজেটের অবশিষ্ট</span><strong class="<?= ($grandBudget-$grandSpent)<0?'p5-negative':'' ?>"><?= p5_cash_view($grandBudget-$grandSpent) ?></strong><small>নির্ধারিত বাজেটের তুলনায়</small></article>
  <article class="p5-kpi"><span class="p5-kpi-label">সরবরাহকারীর বকেয়া</span><strong><?= p5_cash_view($grandDue) ?></strong><small>ক্রয় − পরিশোধ</small></article>
 </section>
 <?php if(is_admin()): ?>
 <section class="p5-surface" id="set-budget"><div class="p5-section-heading"><div><span class="p5-overline">BUDGET SETTINGS</span><h2>প্রকল্পের বাজেট নির্ধারণ</h2><p>চলমান বা নতুন প্রকল্পের অনুমোদিত মোট বাজেট লিখুন।</p></div><span class="p5-tag">Admin only</span></div>
 <form method="post" class="p5-form p5-form-three"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
 <div class="p5-field"><label for="budgetProject">প্রকল্প <b>*</b></label><select id="budgetProject" name="project_id" required><option value="">প্রকল্প নির্বাচন করুন</option><?php foreach($projects as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
 <div class="p5-field"><label for="budgetAmount">অনুমোদিত বাজেট (৳) <b>*</b></label><input id="budgetAmount" name="budget_amount" type="number" step="0.01" min="0" max="999999999999.99" placeholder="যেমন: 2500000.00" required></div>
 <div class="p5-field"><label for="budgetNotes">মন্তব্য <span>(ঐচ্ছিক)</span></label><input id="budgetNotes" name="notes" maxlength="500" placeholder="যেমন: প্রথম অনুমোদিত বাজেট"></div>
 <div class="p5-form-foot"><p>বাজেটের পরিবর্তন Audit Log-এ রেকর্ড হবে।</p><button class="p5-btn p5-btn-primary" type="submit">বাজেট সংরক্ষণ করুন <span aria-hidden="true">↗</span></button></div>
 </form></section>
 <?php endif; ?>
 <section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">BUDGET MONITOR</span><h2>প্রকল্পভিত্তিক বাজেটের অগ্রগতি</h2><p>হিসাবভুক্ত খরচ বাজেট ছাড়ালে সতর্কবার্তা দেখাবে।</p></div><a href="budget_report.php" class="p5-link">বিস্তারিত রিপোর্ট ↗</a></div>
 <?php if ($rows): ?>
 <div class="p5-toolbar"><label class="p5-search"><span class="sr-only">প্রকল্প খুঁজুন</span><input type="search" placeholder="প্রকল্পের নাম দিয়ে খুঁজুন..." data-p5-search></label></div>
 <div class="p5-table-scroll"><table class="p5-table" data-p5-table><thead><tr><th>প্রকল্প</th><th>বাজেট</th><th>অনুমোদিত ব্যয়</th><th>সরবরাহকারী ক্রয়</th><th>হিসাবভুক্ত মোট</th><th>অবশিষ্ট</th><th>অবস্থা</th></tr></thead><tbody><?php foreach($rows as $r): $spent=p5_amount_spent($r); $budget=(float)$r['budget_amount'];$remain=$budget-$spent;$pct=$budget>0?($spent/$budget)*100:0; ?>
 <tr data-p5-row><td><strong><?= e($r['title']) ?></strong><small><?= e(['running'=>'চলমান','completed'=>'সম্পন্ন','closed'=>'বন্ধ'][$r['status']]??$r['status']) ?></small></td><td><?= p5_cash_view($budget) ?></td><td><?= p5_cash_view($r['expense_amount']) ?></td><td><?= p5_cash_view($r['purchase_amount']) ?></td><td><strong><?= p5_cash_view($spent) ?></strong></td><td class="<?= $remain<0?'p5-negative':'' ?>"><?= p5_cash_view($remain) ?></td><td><?php if($budget<=0): ?><span class="p5-pill neutral">বাজেট সেট হয়নি</span><?php elseif($remain<0): ?><span class="p5-pill danger">বাজেট অতিক্রম</span><?php elseif($pct>=80): ?><span class="p5-pill warning"><?= number_format($pct,0) ?>% ব্যবহার</span><?php else: ?><span class="p5-pill success"><?= number_format($pct,0) ?>% ব্যবহার</span><?php endif; ?><div class="p5-progress" role="meter" aria-label="বাজেট ব্যবহার" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= min(100,max(0,round($pct))) ?>"><i style="width:<?= min(100,max(0,$pct)) ?>%"></i></div></td></tr><?php endforeach; ?></tbody></table></div>
 <div class="p5-empty" data-p5-no-match hidden>এই নামে কোনো প্রকল্প পাওয়া যায়নি।</div>
 <?php else: ?><div class="p5-empty">প্রথমে একটি প্রকল্প তৈরি করুন। <a href="projects.php">প্রকল্প তৈরি করতে যান ↗</a></div><?php endif; ?>
 </section>
 <p class="p5-note">হিসাবের নিয়ম: অনুমোদিত Expense এবং Supplier Purchase (সম্পূর্ণ Invoice Value) আলাদাভাবে যোগ হয়েছে। একই ক্রয়ের Bill আবার Expense-এ এন্ট্রি করলে ডাবল কাউন্ট হবে। Employee Advance নিজে খরচ নয়, তাই এখানে যোগ হয়নি।</p>
</div><script src="assets/phase5.js?v=5.0.0" defer></script>
<?php include 'layout_bottom.php'; ?>
