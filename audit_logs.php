<?php
require_once __DIR__.'/phase5_lib.php';require_login();p5_admin();p5_require_ready();$pdo=db();
$search=trim(is_string($_GET['q']??null)?$_GET['q']:'');$search=function_exists('mb_substr')?mb_substr($search,0,80,'UTF-8'):substr($search,0,80);
$action=(string)($_GET['action']??'');
$known=['auth.login','project.created','expense.created','expense.edited','expense.approved','advance.saved','budget.saved','inventory.item.created','inventory.in','inventory.out','supplier.created','purchase.created','payment.created','user.created','permission.updated','year.created','expense_head.created','expense_head.edited'];
if(!in_array($action,$known,true))$action='';
$from=(string)($_GET['from']??'');$to=(string)($_GET['to']??'');if(!valid_date($from))$from='';if(!valid_date($to))$to='';
$where=['1=1'];$params=[];
if($search!==''){$where[]='(a.description LIKE ? OR a.entity_type LIKE ? OR u.name LIKE ?)';$q='%'.str_replace(['%','_'],['\\%','\\_'],$search).'%';$params=array_merge($params,[$q,$q,$q]);}
if($action!==''){$where[]='a.action=?';$params[]=$action;}
if($from!==''){$where[]='a.created_at>=?';$params[]=$from.' 00:00:00';}
if($to!==''){$where[]='a.created_at<=?';$params[]=$to.' 23:59:59';}
$whereSql=implode(' AND ',$where);
if(($_GET['format']??'')==='csv'){
    $st=$pdo->prepare("SELECT a.id,a.created_at,COALESCE(u.name,'Deleted user') actor,a.action,a.entity_type,a.entity_id,a.project_id,a.description,a.ip_address FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id WHERE $whereSql ORDER BY a.id DESC LIMIT 5000");$st->execute($params);$rows=$st->fetchAll();
    p5_csv('jv-audit-'.date('Ymd').'.csv',['ID','Created At','Actor','Action','Entity','Entity ID','Project ID','Description','IP'],array_map('array_values',$rows));
}
$st=$pdo->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id WHERE $whereSql");$st->execute($params);$count=(int)$st->fetchColumn();
$pager=max(1,min(100000,(int)($_GET['page']??1)));$perPage=30;$pages=max(1,(int)ceil($count/$perPage));$pager=min($pager,$pages);$offset=($pager-1)*$perPage;
$st=$pdo->prepare("SELECT a.*,u.name actor_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id WHERE $whereSql ORDER BY a.id DESC LIMIT ? OFFSET ?");
$i=1;foreach($params as $param)$st->bindValue($i++,$param,PDO::PARAM_STR);$st->bindValue($i++,$perPage,PDO::PARAM_INT);$st->bindValue($i++,$offset,PDO::PARAM_INT);$st->execute();$logs=$st->fetchAll();
$urlBase=['q'=>$search,'action'=>$action,'from'=>$from,'to'=>$to];
include 'layout_top.php';
?>
<div class="p5-page" data-p5-page="audit"><section class="p5-hero"><div class="p5-hero-inner"><span class="p5-eyebrow">SECURITY / 05</span><h1>কখন, কী বদলাল—<em>সব রেকর্ড থাকুক।</em></h1><p>Login, Expense Approval, Budget, Purchase, Payment ও Stock Movement-এর Activity Log এখানে পাওয়া যাবে।</p></div><span class="p5-hero-art" aria-hidden="true">◎</span></section>
<section class="p5-kpis"><article class="p5-kpi"><span class="p5-kpi-label">মোট রেকর্ড</span><strong><?= number_format($count) ?></strong><small>বর্তমান Filter অনুযায়ী</small></article><article class="p5-kpi"><span class="p5-kpi-label">প্রতি Page</span><strong><?= $perPage ?></strong><small>Server-side pagination</small></article><article class="p5-kpi"><span class="p5-kpi-label">Security</span><strong>Read-only</strong><small>Delete / Edit Action নেই</small></article></section>
<section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">AUDIT TRAIL</span><h2>Activity Log & Search</h2><p>শুধু Admin দেখতে পারবেন। Filters দিয়ে নির্দিষ্ট কাজ খুঁজুন।</p></div><a class="p5-btn p5-btn-outline" href="?<?= e(http_build_query(array_merge($urlBase,['format'=>'csv']))) ?>">CSV Export</a></div><form method="get" class="p5-form p5-form-four p5-filter-form"><div class="p5-field"><label for="logSearch">Search</label><input type="search" name="q" id="logSearch" value="<?= e($search) ?>" placeholder="User / Module / Details"></div><div class="p5-field"><label for="logAction">Action</label><select id="logAction" name="action"><option value="">সব Action</option><?php foreach($known as $v): ?><option value="<?= e($v) ?>" <?= $action===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></div><div class="p5-field"><label for="logFrom">From Date</label><input id="logFrom" type="date" name="from" value="<?= e($from) ?>"></div><div class="p5-field"><label for="logTo">To Date</label><input id="logTo" type="date" name="to" value="<?= e($to) ?>"></div><div class="p5-form-foot"><div class="p5-action-row"><button class="p5-btn p5-btn-primary" type="submit">Filter Apply করুন ↗</button><a class="p5-btn p5-btn-outline" href="audit_logs.php">Reset</a></div></div></form></section>
<section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">EVENT HISTORY</span><h2>সর্বশেষ কার্যক্রম</h2><p><?= number_format($count) ?>টি রেকর্ড পাওয়া গেছে</p></div><span class="p5-tag">Page <?= $pager ?> / <?= $pages ?></span></div>
<?php if($logs): ?><div class="p5-table-scroll"><table class="p5-table"><thead><tr><th>তারিখ ও সময়</th><th>ব্যবহারকারী</th><th>Action</th><th>Module</th><th>কী হয়েছে</th><th>IP</th></tr></thead><tbody><?php foreach($logs as $a): ?><tr><td><strong><?= e($a['created_at']) ?></strong></td><td><?= e($a['actor_name']??'Deleted user') ?></td><td><span class="p5-pill neutral"><?= e($a['action']) ?></span></td><td><?= e($a['entity_type']) ?><?php if($a['entity_id']): ?><small>#<?= (int)$a['entity_id'] ?></small><?php endif; ?></td><td><?= e($a['description']) ?></td><td><?= e($a['ip_address']??'—') ?></td></tr><?php endforeach; ?></tbody></table></div><div class="p5-pagination"><?php if($pager>1): ?><a class="p5-btn p5-btn-outline" href="?<?= e(http_build_query(array_merge($urlBase,['page'=>$pager-1]))) ?>">← আগের Page</a><?php endif; ?><span><?= $pager ?> / <?= $pages ?></span><?php if($pager<$pages): ?><a class="p5-btn p5-btn-outline" href="?<?= e(http_build_query(array_merge($urlBase,['page'=>$pager+1]))) ?>">পরের Page →</a><?php endif; ?></div><?php else: ?><div class="p5-empty">নির্বাচিত Filter অনুযায়ী কোনো Activity পাওয়া যায়নি।</div><?php endif; ?></section>
<p class="p5-note">Phase 5 migration-এর পর থেকে লগ সংরক্ষিত হয়। পুরোনো কার্যক্রম retroactively দেখানো সম্ভব নয়। IP Address শুধুমাত্র troubleshooting ও security প্রয়োজনে ব্যবহার করুন।</p>
</div>
<?php include 'layout_bottom.php'; ?>
