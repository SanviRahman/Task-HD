<?php
require_once 'config.php'; require_login();
$pdo=db();
if(is_admin()){
 $projects=(int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
 $running=(int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='running'")->fetchColumn();
 $completed=(int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('completed','closed')")->fetchColumn();
 $spent=(float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status='approved'")->fetchColumn();
 $pending=(int)$pdo->query("SELECT COUNT(*) FROM expenses WHERE status='pending'")->fetchColumn();
 $recent=$pdo->query("SELECT e.*,p.title project_title,h.name head_name,u.name user_name FROM expenses e JOIN projects p ON p.id=e.project_id JOIN expense_heads h ON h.id=e.head_id JOIN users u ON u.id=e.entered_by ORDER BY e.id DESC LIMIT 8")->fetchAll();
} else {
 $ids=$pdo->prepare("SELECT project_id FROM project_assignments WHERE user_id=? AND view_project=1"); $ids->execute([user()['id']]); $allowed=array_column($ids->fetchAll(),'project_id');
 $projects=count($allowed); $running=$completed=0; $spent=0; $pending=0; $recent=[];
 if($allowed){$in=implode(',',array_map('intval',$allowed));$running=(int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status='running' AND id IN ($in)")->fetchColumn();$completed=(int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('completed','closed') AND id IN ($in)")->fetchColumn();$spent=(float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE status='approved' AND project_id IN ($in)")->fetchColumn();$pending=(int)$pdo->query("SELECT COUNT(*) FROM expenses WHERE status='pending' AND project_id IN ($in)")->fetchColumn();$recent=$pdo->query("SELECT e.*,p.title project_title,h.name head_name,u.name user_name FROM expenses e JOIN projects p ON p.id=e.project_id JOIN expense_heads h ON h.id=e.head_id JOIN users u ON u.id=e.entered_by WHERE e.project_id IN ($in) ORDER BY e.id DESC LIMIT 8")->fetchAll();}
}
include 'layout_top.php';
?>
<section class="welcome"><div><span class="eyebrow">সার্বিক চিত্র</span><h1>স্বাগতম <?=date('H')<12?'সকাল':(date('H')<18?'দুপুর':'সন্ধ্যা')?>, <?=e(user()['name'])?></h1><p>আপনার নির্মাণ প্রকল্পগুলোর সর্বশেষ সারসংক্ষেপ।</p></div><a class="btn primary" href="projects.php">প্রকল্প দেখুন <span>→</span></a></section>
<section class="stats">
 <article class="stat"><div class="stat-icon blue">▦</div><span>মোট প্রকল্প</span><strong><?=$projects?></strong><small>নির্ধারিত / তৈরি প্রকল্প</small></article>
 <article class="stat"><div class="stat-icon green">↗</div><span>চলমান প্রকল্প</span><strong><?=$running?></strong><small>বর্তমানে কাজ চলছে</small></article>
 <article class="stat"><div class="stat-icon gold">✓</div><span>সম্পন্ন প্রকল্প</span><strong><?=$completed?></strong><small>সম্পন্ন অথবা বন্ধ</small></article>
 <article class="stat"><div class="stat-icon violet">৳</div><span>অনুমোদিত ব্যয়</span><strong>৳<?=number_format($spent,2)?></strong><small><?=$pending?> অনুমোদনের অপেক্ষায়</small></article>
</section>
<section class="panel"><div class="panel-head"><div><h2>সাম্প্রতিক ব্যয়</h2><p>প্রকল্পের সর্বশেষ ব্যয়ের হিসাব</p></div><a href="expenses.php" class="text-link">সব দেখুন →</a></div>
<div class="table-wrap"><table><thead><tr><th>প্রকল্প</th><th>ব্যয়ের খাত</th><th>তারিখ</th><th>এন্ট্রি করেছেন</th><th>টাকার পরিমাণ</th><th>অবস্থা</th></tr></thead><tbody>
<?php foreach($recent as $r):?><tr><td><b><?=e($r['project_title'])?></b></td><td><?=e($r['head_name'])?></td><td><?=e($r['entry_date'])?></td><td><?=e($r['user_name'])?></td><td class="money">৳<?=number_format((float)$r['amount'],2)?></td><td><span class="status <?=e($r['status'])?>"><?=e(ucfirst($r['status']))?></span></td></tr><?php endforeach;?>
<?php if(!$recent):?><tr><td colspan="6" class="empty">এখনো কোনো ব্যয়ের তথ্য নেই.</td></tr><?php endif;?>
</tbody></table></div></section>
<?php include 'layout_bottom.php'; ?>
