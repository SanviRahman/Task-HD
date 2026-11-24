<?php
require_once __DIR__.'/phase5_lib.php';
require_login(); p5_require_ready();
$pdo=db();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    p5_admin(); verify_csrf();
    $pid=(int)($_POST['project_id']??0);
    $amount=p5_money($_POST['budget_amount']??'',true);
    $reason=p5_get_string('revision_reason',350);
    p5_project($pid);
    if ($amount===null) p5_bad_request('সঠিক বাজেট লিখুন (০ বা তার বেশি)।');
    if ($reason==='') p5_bad_request('বাজেট পরিবর্তনের কারণ লিখুন।');
    try {
        $pdo->beginTransaction();
        $st=$pdo->prepare('SELECT budget_amount FROM project_budgets WHERE project_id=? FOR UPDATE');
        $st->execute([$pid]);
        $previous=$st->fetchColumn();
        if ($previous===false) {
            $st=$pdo->prepare('INSERT INTO project_budgets(project_id,budget_amount,notes,updated_by) VALUES(?,?,?,?)');
            $st->execute([$pid,$amount,$reason,(int)user()['id']]);
            p5_audit('budget.saved','project_budgets',$pid,$pid,'Initial budget: '.$amount.' BDT; Reason: '.$reason);
        } else {
            $previous=number_format((float)$previous,2,'.','');
            $st=$pdo->prepare('UPDATE project_budgets SET budget_amount=?,notes=?,updated_by=?,updated_at=CURRENT_TIMESTAMP WHERE project_id=?');
            $st->execute([$amount,$reason,(int)user()['id'],$pid]);
            p5_audit('budget.revised','project_budgets',$pid,$pid,'Previous: '.$previous.' BDT; New: '.$amount.' BDT; Reason: '.$reason);
        }
        $pdo->commit();
        flash('প্রকল্পের বাজেট সংশোধন করা হয়েছে।');redirect('budgets.php');
    } catch (Throwable $ex) { if($pdo->inTransaction())$pdo->rollBack(); throw $ex; }
}
$rows=p5_budget_rows();
$grandBudget=$grandSpent=$grandDue=0.0;
foreach ($rows as $r) { $grandBudget+=(float)$r['budget_amount'];$grandSpent+=p5_amount_spent($r);$grandDue+=(float)$r['purchase_amount']-(float)$r['supplier_paid']; }
$historyWhere=p5_project_where('a.project_id','view_report');
$history=$pdo->query("SELECT a.created_at,a.action,a.description,p.title,u.name actor_name FROM audit_logs a LEFT JOIN projects p ON p.id=a.project_id LEFT JOIN users u ON u.id=a.actor_id WHERE a.entity_type='project_budgets' AND a.action IN ('budget.saved','budget.revised') AND $historyWhere ORDER BY a.id DESC LIMIT 100")->fetchAll();
include 'layout_top.php';
?>
<div class="p5-page" data-p5-page="budgets">
 <section class="p5-hero"><div class="p5-hero-inner"><span class="p5-eyebrow">PROJECT FINANCE / 01</span><h1>প্রকল্পের বাজেট, <em>পরিষ্কার হিসাব।</em></h1><p>প্রতিটি প্রকল্পের অনুমোদিত বাজেট, খরচ, অবশিষ্ট অর্থ এবং বাজেট পরিবর্তনের ইতিহাস দেখুন।</p></div><span class="p5-hero-art" aria-hidden="true">৳</span></section>
 <section class="p5-kpis" aria-label="বাজেট সারসংক্ষেপ">
  <article class="p5-kpi"><span class="p5-kpi-label">মোট নির্ধারিত বাজেট</span><strong><?= p5_cash_view($grandBudget) ?></strong><small><?= count($rows) ?>টি দৃশ্যমান প্রকল্প</small></article>
  <article class="p5-kpi"><span class="p5-kpi-label">মোট হিসাবভুক্ত খরচ</span><strong><?= p5_cash_view($grandSpent) ?></strong><small>অনুমোদিত ব্যয় + সরবরাহকারী ক্রয়</small></article>
  <article class="p5-kpi"><span class="p5-kpi-label">বাজেটের অবশিষ্ট</span><strong class="<?= ($grandBudget-$grandSpent)<0?'p5-negative':'' ?>"><?= p5_cash_view($grandBudget-$grandSpent) ?></strong><small>নির্ধারিত বাজেটের তুলনায়</small></article>
  <article class="p5-kpi"><span class="p5-kpi-label">সরবরাহকারীর বকেয়া</span><strong><?= p5_cash_view($grandDue) ?></strong><small>ক্রয় − পরিশোধ</small></article>
 </section>
 <?php if(is_admin()): ?>
 <section class="p5-surface" id="revise-budget"><div class="p5-section-heading"><div><span class="p5-overline">BUDGET REVISION</span><h2>প্রকল্পের বাজেট সংশোধন</h2><p>প্রকল্প তৈরির সময় নির্ধারিত বাজেট পরিবর্তনের প্রয়োজন হলে এখানে সংশোধন করুন।</p></div><span class="p5-tag">Admin only</span></div>
 <?php if($rows): ?>
 <form method="post" class="p5-form p5-form-three"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
 <div class="p5-field"><label for="revisionProject">প্রকল্প <b>*</b></label><select id="revisionProject" name="project_id" required><option value="">প্রকল্প নির্বাচন করুন</option><?php foreach($rows as $p): ?><option value="<?= (int)$p['id'] ?>" data-budget="<?= e(number_format((float)$p['budget_amount'],2,'.','')) ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
 <div class="p5-field"><label for="currentBudget">বর্তমান অনুমোদিত বাজেট</label><input id="currentBudget" type="text" value="" placeholder="প্রকল্প নির্বাচন করুন" readonly></div>
 <div class="p5-field"><label for="newBudget">সংশোধিত বাজেট (৳) <b>*</b></label><input id="newBudget" name="budget_amount" type="number" step="0.01" min="0" max="999999999999.99" placeholder="যেমন: 2500000.00" required></div>
 <div class="p5-field p5-span2"><label for="revisionReason">বাজেট পরিবর্তনের কারণ <b>*</b></label><input id="revisionReason" name="revision_reason" maxlength="350" placeholder="যেমন: নির্মাণ সামগ্রীর মূল্য বৃদ্ধি পাওয়ায় বাজেট সংশোধন" required></div>
 <div class="p5-form-foot"><p>প্রতিটি সংশোধনের আগের ও নতুন বাজেট, তারিখ, ব্যবহারকারী এবং কারণ সংরক্ষিত হবে।</p><button class="p5-btn p5-btn-primary" type="submit">বাজেট সংশোধন করুন <span aria-hidden="true">↗</span></button></div>
 </form>
 <?php else: ?><div class="p5-empty">বাজেট সংশোধনের জন্য কোনো প্রকল্প পাওয়া যায়নি।</div><?php endif; ?></section>
 <?php endif; ?>
 <section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">BUDGET OVERVIEW</span><h2>প্রকল্পভিত্তিক বাজেটের অগ্রগতি</h2><p>হিসাবভুক্ত খরচ বাজেট ছাড়ালে সতর্কবার্তা দেখাবে।</p></div><a href="budget_report.php" class="p5-link">বিস্তারিত রিপোর্ট ↗</a></div>
 <?php if ($rows): ?>
 <div class="p5-toolbar"><label class="p5-search"><span class="sr-only">প্রকল্প খুঁজুন</span><input type="search" placeholder="প্রকল্পের নাম দিয়ে খুঁজুন..." data-p5-search></label></div>
 <div class="p5-table-scroll"><table class="p5-table" data-p5-table><thead><tr><th>প্রকল্প</th><th>বাজেট</th><th>অনুমোদিত ব্যয়</th><th>সরবরাহকারী ক্রয়</th><th>হিসাবভুক্ত মোট</th><th>অবশিষ্ট</th><th>অবস্থা</th></tr></thead><tbody><?php foreach($rows as $r): $spent=p5_amount_spent($r); $budget=(float)$r['budget_amount'];$remain=$budget-$spent;$pct=$budget>0?($spent/$budget)*100:0; ?>
 <tr data-p5-row><td><strong><?= e($r['title']) ?></strong><small><?= e(['running'=>'চলমান','completed'=>'সম্পন্ন','closed'=>'বন্ধ'][$r['status']]??$r['status']) ?></small></td><td><?= p5_cash_view($budget) ?></td><td><?= p5_cash_view($r['expense_amount']) ?></td><td><?= p5_cash_view($r['purchase_amount']) ?></td><td><strong><?= p5_cash_view($spent) ?></strong></td><td class="<?= $remain<0?'p5-negative':'' ?>"><?= p5_cash_view($remain) ?></td><td><?php if($budget<=0): ?><span class="p5-pill neutral">বাজেট সেট হয়নি</span><?php elseif($remain<0): ?><span class="p5-pill danger">বাজেট অতিক্রম</span><?php elseif($pct>=80): ?><span class="p5-pill warning"><?= number_format($pct,0) ?>% ব্যবহার</span><?php else: ?><span class="p5-pill success"><?= number_format($pct,0) ?>% ব্যবহার</span><?php endif; ?><div class="p5-progress" role="meter" aria-label="বাজেট ব্যবহার" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= min(100,max(0,round($pct))) ?>"><i style="width:<?= min(100,max(0,$pct)) ?>%"></i></div></td></tr><?php endforeach; ?></tbody></table></div>
 <div class="p5-empty" data-p5-no-match hidden>এই নামে কোনো প্রকল্প পাওয়া যায়নি।</div>
 <?php else: ?><div class="p5-empty">প্রথমে একটি প্রকল্প তৈরি করুন। <a href="projects.php">প্রকল্প তৈরি করতে যান ↗</a></div><?php endif; ?>
 </section>
 <section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">REVISION HISTORY</span><h2>বাজেট সংশোধনের ইতিহাস</h2><p>সর্বশেষ ১০০টি বাজেট নির্ধারণ ও সংশোধনের রেকর্ড।</p></div><span class="p5-tag"><?= count($history) ?>টি রেকর্ড</span></div>
 <?php if($history): ?><div class="p5-table-scroll"><table class="p5-table"><thead><tr><th>তারিখ</th><th>প্রকল্প</th><th>আগের বাজেট</th><th>নতুন বাজেট</th><th>পরিবর্তনকারী</th><th>কারণ</th></tr></thead><tbody>
 <?php foreach($history as $h): $previous=null;$next=null;$reason=$h['description'];if(preg_match('/^Previous: ([0-9]+(?:\.[0-9]{1,2})?) BDT; New: ([0-9]+(?:\.[0-9]{1,2})?) BDT; Reason: (.*)$/u',$reason,$m)){ $previous=(float)$m[1];$next=(float)$m[2];$reason=$m[3]; }elseif(preg_match('/^Initial budget: ([0-9]+(?:\.[0-9]{1,2})?) BDT; Reason: (.*)$/u',$reason,$m)){ $next=(float)$m[1];$reason=$m[2]; }elseif(preg_match('/^Initial budget BDT ([0-9]+(?:\.[0-9]{1,2})?)$/u',$reason,$m)){ $next=(float)$m[1];$reason='প্রকল্প তৈরির সময় প্রাথমিক বাজেট নির্ধারণ'; }elseif(preg_match('/^Budget updated to BDT ([0-9]+(?:\.[0-9]{1,2})?)$/u',$reason,$m)){ $next=(float)$m[1];$reason='বাজেট সংরক্ষণ'; } ?>
 <tr><td><?= e($h['created_at']) ?></td><td><strong><?= e($h['title']??'Deleted project') ?></strong><small><?= $h['action']==='budget.revised'?'বাজেট সংশোধন':'বাজেট নির্ধারণ' ?></small></td><td><?= $previous===null?'—':p5_cash_view($previous) ?></td><td><?= $next===null?'—':p5_cash_view($next) ?></td><td><?= e($h['actor_name']??'Deleted user') ?></td><td><?= e($reason) ?></td></tr>
 <?php endforeach; ?></tbody></table></div>
 <?php else: ?><div class="p5-empty">এখনো কোনো বাজেট নির্ধারণ বা সংশোধনের রেকর্ড নেই।</div><?php endif; ?></section>
 <p class="p5-note">হিসাবের নিয়ম: অনুমোদিত Expense এবং Supplier Purchase (সম্পূর্ণ Invoice Value) আলাদাভাবে যোগ হয়েছে। একই ক্রয়ের Bill আবার Expense-এ এন্ট্রি করলে ডাবল কাউন্ট হবে। Employee Advance নিজে খরচ নয়, তাই এখানে যোগ হয়নি।</p>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const project=document.getElementById('revisionProject');
 const current=document.getElementById('currentBudget');
 if(!project||!current)return;
 const update=function(){const option=project.options[project.selectedIndex];const amount=option&&option.dataset.budget?Number(option.dataset.budget):null;current.value=amount===null?'':'৳'+amount.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});};
 project.addEventListener('change',update);
 update();
});
</script>
<script src="assets/phase5.js?v=5.0.0" defer></script>
<?php include 'layout_bottom.php'; ?>
