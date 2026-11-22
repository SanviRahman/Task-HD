<?php
require_once 'config.php';require_login();$pdo=db();
$projects=array_values(array_filter(allowed_projects('view_project'),fn($p)=>can('view_report',(int)$p['id'])));
$pid=(int)($_GET['project_id']??0);$from=(string)($_GET['from']??date('Y-m-01'));$to=(string)($_GET['to']??date('Y-m-d'));
if(!valid_date($from)||!valid_date($to)||$from>$to){http_response_code(400);exit('Invalid dates');}
if($pid&&!can('view_report',$pid)){http_response_code(403);exit('Forbidden');}
$advances=[];$expenses=[];$totAdvance=0;$totExpense=0;
if($pid){
 $st=$pdo->prepare('SELECT a.employee_id,u.name,SUM(a.amount) total FROM employee_advances a JOIN users u ON u.id=a.employee_id WHERE a.project_id=? AND a.advance_date BETWEEN ? AND ? GROUP BY a.employee_id,u.name ORDER BY u.name');$st->execute([$pid,$from,$to]);$advances=$st->fetchAll();
 $st=$pdo->prepare('SELECT e.advance_employee_id employee_id,COALESCE(u.name,"Unallocated project expense") name,h.name head,SUM(e.amount) total FROM expenses e JOIN expense_heads h ON h.id=e.head_id LEFT JOIN users u ON u.id=e.advance_employee_id WHERE e.project_id=? AND e.entry_date BETWEEN ? AND ? AND e.status="approved" GROUP BY e.advance_employee_id,u.name,h.name ORDER BY name,head');$st->execute([$pid,$from,$to]);$expenses=$st->fetchAll();
 $totAdvance=array_sum(array_column($advances,'total'));$totExpense=array_sum(array_column($expenses,'total'));
}
include 'layout_top.php';?>
<style>@media print{.sidebar,.topbar,.no-print{display:none!important}.main,.content{margin:0!important;padding:0!important}body{background:#fff!important}}</style>
<section class="page-title"><div><h1>Project Advance & Expense Report</h1><p>Daily, monthly or full project period. Approved expenses only.</p></div></section>
<section class="panel no-print"><form method="get" class="form-grid"><div><label>Project</label><select name="project_id" required><option value="">Select</option><?php foreach($projects as $p):?><option value="<?=e($p['id'])?>" <?=$pid===(int)$p['id']?'selected':''?>><?=e($p['title'])?></option><?php endforeach;?></select></div><div><label>From</label><input type="date" name="from" value="<?=e($from)?>" required></div><div><label>To</label><input type="date" name="to" value="<?=e($to)?>" required></div><button class="btn primary">View Report</button></form><p><button class="btn" onclick="window.print()">Print / Save as PDF</button></p></section>
<?php if($pid):?><section class="panel"><h2><?=e($from)?> — <?=e($to)?></h2><p>Advances issued: <b>৳<?=number_format($totAdvance,2)?></b> | Approved expenses: <b>৳<?=number_format($totExpense,2)?></b> | Period net: <b>৳<?=number_format($totAdvance-$totExpense,2)?></b></p><p><small>Period net is not the lifetime balance; opening balances and transactions before the selected period are excluded. For running balances use Employee Ledger.</small></p><h3>Employee advances</h3><div class="table-wrap"><table><thead><tr><th>Employee</th><th>Advance</th></tr></thead><tbody><?php foreach($advances as $a):?><tr><td><?=e($a['name'])?></td><td>৳<?=number_format((float)$a['total'],2)?></td></tr><?php endforeach;?></tbody></table></div><h3>Approved expenses by employee and head</h3><div class="table-wrap"><table><thead><tr><th>Employee</th><th>Expense head</th><th>Amount</th></tr></thead><tbody><?php foreach($expenses as $x):?><tr><td><?=e($x['name'])?></td><td><?=e($x['head'])?></td><td>৳<?=number_format((float)$x['total'],2)?></td></tr><?php endforeach;?></tbody></table></div></section><?php endif;?>
<?php include 'layout_bottom.php';?>
