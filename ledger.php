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
include 'layout_top.php';?>
<style>@media print{.sidebar,.topbar,.no-print{display:none!important}.main,.content{margin:0!important;padding:0!important}body{background:white!important}.panel{box-shadow:none!important}table{font-size:11px}}</style>
<section class="page-title"><div><h1>Employee Advance Ledger</h1><p>Project and employee-wise running advance balance. Only approved expenses are deducted.</p></div></section>
<section class="panel no-print"><h2>Ledger filters</h2><form method="get" class="form-grid"><div><label>Project</label><select name="project_id" required><option value="">Select</option><?php foreach($projects as $p):?><option value="<?=e($p['id'])?>" <?=$pid===(int)$p['id']?'selected':''?>><?=e($p['title'])?></option><?php endforeach;?></select></div><div><label>Employee</label><select name="employee_id" required><option value="">Select</option><?php foreach($employees as $u):?><option value="<?=e($u['id'])?>" <?=$eid===(int)$u['id']?'selected':''?>><?=e($u['name'])?></option><?php endforeach;?></select></div><div><label>From</label><input type="date" name="from" value="<?=e($from)?>" required></div><div><label>To</label><input type="date" name="to" value="<?=e($to)?>" required></div><button class="btn primary">View Ledger</button></form><p><button type="button" class="btn" onclick="window.print()">Print / Save as PDF</button></p></section>
<?php if(is_admin()):?><section class="panel no-print"><h2>Set opening balance (Admin)</h2><p>Set this once per employee and project, before the first recorded transaction. Existing opening balances can be corrected here.</p><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div><label>Project</label><select name="project_id" required><?php foreach(allowed_projects() as $p):?><option value="<?=e($p['id'])?>" <?=$pid===(int)$p['id']?'selected':''?>><?=e($p['title'])?></option><?php endforeach;?></select></div><div><label>Employee</label><select name="employee_id" required><?php foreach($employees as $u):?><option value="<?=e($u['id'])?>" <?=$eid===(int)$u['id']?'selected':''?>><?=e($u['name'])?></option><?php endforeach;?></select></div><div><label>Opening date</label><input type="date" name="opening_date" value="<?=e($opening['opening_date']??date('Y-m-01'))?>" required></div><div><label>Opening amount (৳)</label><input type="number" name="amount" min="0" step="0.01" value="<?=e($opening['amount']??'0.00')?>" required></div><div><label>Note</label><input name="note" maxlength="500" value="<?=e($opening['note']??'')?>"></div><button class="btn primary">Save Opening</button></form></section><?php endif;?>
<?php if($pid&&$eid):?><section class="panel"><h2>Ledger: <?=e($from)?> to <?=e($to)?></h2><?php if($message):?><p><?=e($message)?></p><?php else:?>
<p>Opening balance: <b>৳<?=number_format($periodOpening,2)?></b> | Advances: <b>৳<?=number_format($advances,2)?></b> | Approved expenses: <b>৳<?=number_format($expenses,2)?></b> | Closing balance: <b>৳<?=number_format($closing,2)?></b></p><p><small>Opening balance includes all earlier recorded advances and approved expenses from the opening date (if set). A negative closing balance means expenses exceed advances.</small></p>
<div class="table-wrap"><table><thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Advance (৳)</th><th>Expense (৳)</th><th>Running Balance (৳)</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['dt'])?></td><td><?=e($r['kind'])?></td><td><?=e($r['note'])?></td><td><?=$r['kind']==='Advance'?number_format((float)$r['amount'],2):'—'?></td><td><?=$r['kind']==='Expense'?number_format((float)$r['amount'],2):'—'?></td><td><?=number_format((float)$r['balance'],2)?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section><?php endif;?>
<?php include 'layout_bottom.php';?>
