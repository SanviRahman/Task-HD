<?php
require_once 'config.php'; require_login(); $pdo=db();
$projects=is_admin()
 ? $pdo->query("SELECT id,title FROM projects ORDER BY title")->fetchAll()
 : (function()use($pdo){$s=$pdo->prepare("SELECT p.id,p.title FROM projects p JOIN project_assignments a ON a.project_id=p.id WHERE a.user_id=? AND a.add_head=1 ORDER BY p.title");$s->execute([user()['id']]);return $s->fetchAll();})();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $pid=(int)($_POST['project_id']??0);
 if(!is_admin() && !can('add_head',$pid)){http_response_code(403);exit('অনুমতি নেই');}
 if($_POST['action']==='add'){
  $name=trim($_POST['name']??'');
  if($name!==''){$s=$pdo->prepare("INSERT INTO expense_heads(project_id,name) VALUES(?,?)");$s->execute([$pid,$name]);flash('ব্যয়ের খাত যোগ হয়েছে।');}
 } elseif($_POST['action']==='edit'){
  $name=trim($_POST['name']??'');$hid=(int)$_POST['head_id'];
  if($name!==''){$s=$pdo->prepare("UPDATE expense_heads SET name=? WHERE id=? AND project_id=?");$s->execute([$name,$hid,$pid]);flash('ব্যয়ের খাতের নাম পরিবর্তন হয়েছে।');}
 }
 redirect('expense_heads.php');
}
$rows=is_admin()
 ? $pdo->query("SELECT h.*,p.title project_title FROM expense_heads h JOIN projects p ON p.id=h.project_id ORDER BY p.title,h.name")->fetchAll()
 : (function()use($pdo){$s=$pdo->prepare("SELECT h.*,p.title project_title FROM expense_heads h JOIN projects p ON p.id=h.project_id JOIN project_assignments a ON a.project_id=p.id WHERE a.user_id=? AND a.add_head=1 ORDER BY p.title,h.name");$s->execute([user()['id']]);return $s->fetchAll();})();
include 'layout_top.php'; ?>
<section class="page-title"><div><span class="eyebrow">প্রকল্প ব্যবস্থাপনা</span><h1>ব্যয়ের খাত</h1><p>প্রতিটি প্রকল্পের জন্য আলাদা ব্যয়ের খাত তৈরি ও সম্পাদনা করুন।</p></div></section>
<section class="panel"><div class="panel-head"><h2>নতুন ব্যয়ের খাত যোগ করুন</h2></div>
<?php if(!$projects):?><p class="empty">আপনার কোনো প্রকল্পে খাত তৈরির অনুমতি নেই। প্রশাসকের সঙ্গে যোগাযোগ করুন।</p><?php else:?>
<form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="add"><div><label>প্রকল্প নির্বাচন</label><select name="project_id" required><?php foreach($projects as $p):?><option value="<?=$p['id']?>"><?=e($p['title'])?></option><?php endforeach;?></select></div><div><label>ব্যয়ের খাতের নাম</label><input name="name" placeholder="যেমন: সিমেন্ট, রড, শ্রমিক খরচ" required></div><button class="btn primary">খাত যোগ করুন</button></form><?php endif;?></section>
<section class="panel"><div class="panel-head"><h2>তৈরি করা ব্যয়ের খাত</h2></div><div class="table-wrap"><table><thead><tr><th>প্রকল্প</th><th>ব্যয়ের খাত</th><th>নাম পরিবর্তন</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=e($r['project_title'])?></td><td><b><?=e($r['name'])?></b></td><td><?php if(is_admin()||can('add_head',(int)$r['project_id'])):?><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="edit"><input type="hidden" name="project_id" value="<?=$r['project_id']?>"><input type="hidden" name="head_id" value="<?=$r['id']?>"><input name="name" value="<?=e($r['name'])?>" required><button class="btn">সংরক্ষণ</button></form><?php else:?>—<?php endif;?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="3" class="empty">এখনো কোনো ব্যয়ের খাত তৈরি হয়নি।</td></tr><?php endif;?></tbody></table></div></section>
<?php include 'layout_bottom.php'; ?>