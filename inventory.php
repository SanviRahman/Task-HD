<?php
require_once __DIR__.'/phase5_lib.php';
require_login();p5_require_ready();$pdo=db();
$units=['bag'=>'ব্যাগ','kg'=>'কেজি','ton'=>'টন','cft'=>'CFT','pcs'=>'পিস','liter'=>'লিটার','meter'=>'মিটার','sqm'=>'বর্গমিটার','other'=>'অন্যান্য'];
if($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();$pid=(int)($_POST['project_id']??0);
    p5_project($pid,'add_expense');
    $act=p5_get_string('action',30);
    if($act==='add_item') {
        $check=$pdo->prepare("SELECT id FROM projects WHERE id=? AND status!='closed'");$check->execute([$pid]);if(!$check->fetchColumn())p5_bad_request('বন্ধ Project-এ নতুন Material যোগ করা যাবে না।');
        $name=p5_get_string('item_name',150);$unit=p5_get_string('unit',20);$minimum=p5_qty($_POST['minimum_qty']??'',true);
        if(!$name||!array_key_exists($unit,$units)||$minimum===null) p5_bad_request('উপকরণ, ইউনিট এবং ন্যূনতম পরিমাণ ঠিকভাবে লিখুন।');
        try {$pdo->beginTransaction();
            $st=$pdo->prepare('INSERT INTO inventory_items(project_id,item_name,unit,minimum_qty,created_by) VALUES(?,?,?,?,?)');$st->execute([$pid,$name,$unit,$minimum,(int)user()['id']]);$id=(int)$pdo->lastInsertId();
            p5_audit('inventory.item.created','inventory_items',$id,$pid,'Material added: '.$name);
            $pdo->commit();flash('উপকরণ যোগ হয়েছে।');
        } catch(PDOException $ex) { if($pdo->inTransaction())$pdo->rollBack();if($ex->getCode()==='23000')flash('এই প্রকল্পে একই নামের উপকরণ আগে থেকেই আছে।','error');else throw $ex; }
        redirect('inventory.php?project_id='.$pid);
    }
    if($act==='movement') {
        $nonce=$_POST['movement_nonce']??'';
        if(!is_string($nonce)||!hash_equals((string)($_SESSION['p5_movement_nonce']??''),$nonce)||$nonce===''){http_response_code(409);exit('Stock form expired or already submitted. Refresh the page.');}
        unset($_SESSION['p5_movement_nonce']);
        $id=(int)($_POST['item_id']??0);$kind=p5_get_string('movement_type',10);$qty=p5_qty($_POST['quantity']??'');$date=p5_get_string('movement_date',10);$note=p5_get_string('note',500);
        if($id<1 || !in_array($kind,['in','out'],true) || $qty===null || !valid_date($date)) p5_bad_request('Stock, পরিমাণ ও তারিখ সঠিকভাবে পূরণ করুন।');
        try{$pdo->beginTransaction();p5_stock_move($pdo,$id,$pid,$kind,$qty,$date,null,$note);
            p5_audit('inventory.'.$kind,'inventory_movements',(int)$pdo->lastInsertId(),$pid,($kind==='in'?'Stock in ':'Stock out ').$qty.' of item #'.$id);
            $pdo->commit();flash('Stock-এর লেনদেন সফলভাবে সংরক্ষিত হয়েছে।');
        }catch(InvalidArgumentException $ex){if($pdo->inTransaction())$pdo->rollBack();flash($ex->getMessage(),'error');}
         catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();throw $ex;}
        redirect('inventory.php?project_id='.$pid);
    }
    p5_bad_request('Unknown action.');
}
$_SESSION['p5_movement_nonce']=bin2hex(random_bytes(16));
$projects=allowed_projects('view_project');$pid=p5_selected_project();
$items=[];$movements=[];$low=0;$totalQty=0;
if($pid){
    $st=$pdo->prepare('SELECT * FROM inventory_items WHERE project_id=? ORDER BY item_name');$st->execute([$pid]);$items=$st->fetchAll();
    foreach($items as $i){$totalQty+=(float)$i['quantity_on_hand'];if((float)$i['minimum_qty']>0&&(float)$i['quantity_on_hand']<=(float)$i['minimum_qty'])$low++;}
    $st=$pdo->prepare('SELECT m.*,i.item_name,i.unit,u.name actor FROM inventory_movements m JOIN inventory_items i ON i.id=m.item_id JOIN users u ON u.id=m.created_by WHERE i.project_id=? ORDER BY m.id DESC LIMIT 50');$st->execute([$pid]);$movements=$st->fetchAll();
}
$editable=$pid && can('add_expense',$pid);
include 'layout_top.php';
?>
<div class="p5-page" data-p5-page="inventory">
<section class="p5-hero"><div class="p5-hero-inner"><span class="p5-eyebrow">SITE OPERATIONS / 02</span><h1>নির্মাণ উপকরণ, <em>হাতের মুঠোয়।</em></h1><p>কোন প্রকল্পে কী পরিমাণ Material আছে, কতটা এসেছে এবং কতটা ব্যবহার হয়েছে—সব তথ্য এক জায়গায়।</p></div><span class="p5-hero-art" aria-hidden="true">▦</span></section>
<div class="p5-surface"><form method="get" class="p5-selectbar"><label for="projectSelector">প্রকল্প নির্বাচন করুন</label><select id="projectSelector" name="project_id" onchange="this.form.submit()"><option value="">একটি প্রকল্প বাছুন</option><?php foreach($projects as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)$p['id']===$pid?'selected':'' ?>><?= e($p['title']) ?></option><?php endforeach; ?></select><noscript><button class="p5-btn p5-btn-primary" type="submit">দেখুন</button></noscript></form></div>
<section class="p5-kpis"><article class="p5-kpi"><span class="p5-kpi-label">উপকরণের ধরন</span><strong><?= count($items) ?></strong><small>নির্বাচিত প্রকল্প</small></article><article class="p5-kpi"><span class="p5-kpi-label">কম Stock সতর্কতা</span><strong><?= $low ?></strong><small>ন্যূনতম সীমা ছুঁয়েছে</small></article><article class="p5-kpi"><span class="p5-kpi-label">সাম্প্রতিক Movement</span><strong><?= count($movements) ?></strong><small>সর্বশেষ ৫০টি লেনদেন</small></article></section>
<?php if($editable): ?>
<div class="p5-duo">
<section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">MATERIAL MASTER</span><h2>নতুন উপকরণ যোগ করুন</h2><p>প্রকল্পের উপকরণ ও ন্যূনতম Stock নির্ধারণ করুন।</p></div></div><form method="post" class="p5-form p5-form-two"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add_item"><input type="hidden" name="project_id" value="<?= $pid ?>">
<div class="p5-field p5-span2"><label for="itemName">উপকরণের নাম <b>*</b></label><input name="item_name" id="itemName" maxlength="150" placeholder="যেমন: Cement (OPC)" required></div><div class="p5-field"><label for="itemUnit">ইউনিট <b>*</b></label><select id="itemUnit" name="unit" required><?php foreach($units as $v=>$label): ?><option value="<?= e($v) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div><div class="p5-field"><label for="minimumQty">ন্যূনতম Stock</label><input type="number" name="minimum_qty" id="minimumQty" min="0" step="0.001" value="0" placeholder="যেমন: 20"></div><div class="p5-form-foot"><button class="p5-btn p5-btn-primary" type="submit">উপকরণ যোগ করুন ↗</button></div></form></section>
<section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">STOCK MOVEMENT</span><h2>Stock In / Out</h2><p>প্রকল্পে উপকরণ গ্রহণ বা ব্যবহারের রেকর্ড রাখুন।</p></div></div><form method="post" class="p5-form p5-form-two"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="movement"><input type="hidden" name="movement_nonce" value="<?= e($_SESSION['p5_movement_nonce']) ?>"><input type="hidden" name="project_id" value="<?= $pid ?>">
<div class="p5-field p5-span2"><label for="moveItem">উপকরণ <b>*</b></label><select id="moveItem" name="item_id" required><option value="">উপকরণ নির্বাচন করুন</option><?php foreach($items as $i): ?><option value="<?= (int)$i['id'] ?>"><?= e($i['item_name']) ?> (<?= p5_qty_view($i['quantity_on_hand']) ?> <?= e($i['unit']) ?>)</option><?php endforeach; ?></select></div><div class="p5-field"><label for="moveType">Movement <b>*</b></label><select id="moveType" name="movement_type"><option value="in">Stock In — গ্রহণ</option><option value="out">Stock Out — ব্যবহার</option></select></div><div class="p5-field"><label for="moveQty">পরিমাণ <b>*</b></label><input type="number" name="quantity" id="moveQty" min="0.001" step="0.001" placeholder="যেমন: 15" required></div><div class="p5-field"><label for="moveDate">তারিখ <b>*</b></label><input type="date" name="movement_date" id="moveDate" value="<?= date('Y-m-d') ?>" required></div><div class="p5-field"><label for="moveNote">বিবরণ</label><input name="note" id="moveNote" maxlength="500" placeholder="যেমন: ২ নম্বর সাইটে ব্যবহার"></div><div class="p5-form-foot"><button class="p5-btn p5-btn-primary" type="submit">Stock Movement Save ↗</button></div></form></section>
</div><?php endif; ?>
<section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">CURRENT BALANCE</span><h2>উপকরণের বর্তমান Stock</h2><p>একেকটি ইউনিট আলাদা—ভিন্ন ইউনিট একসঙ্গে যোগ করা হয়নি।</p></div><span class="p5-tag"><?= count($items) ?>টি Material</span></div>
<?php if($items): ?><div class="p5-toolbar"><label class="p5-search"><span class="sr-only">Material Search</span><input type="search" placeholder="উপকরণের নাম দিয়ে খুঁজুন..." data-p5-search></label></div><div class="p5-table-scroll"><table class="p5-table" data-p5-table><thead><tr><th>উপকরণ</th><th>ইউনিট</th><th>বর্তমান Stock</th><th>সতর্কতার সীমা</th><th>অবস্থা</th></tr></thead><tbody><?php foreach($items as $i): $isLow=(float)$i['minimum_qty']>0&&(float)$i['quantity_on_hand']<=(float)$i['minimum_qty']; ?><tr data-p5-row><td><strong><?= e($i['item_name']) ?></strong></td><td><?= e($units[$i['unit']]??$i['unit']) ?></td><td><strong><?= p5_qty_view($i['quantity_on_hand']) ?></strong></td><td><?= p5_qty_view($i['minimum_qty']) ?></td><td><span class="p5-pill <?= $isLow?'warning':'success' ?>"><?= $isLow?'Stock কম':'সঠিক আছে' ?></span></td></tr><?php endforeach; ?></tbody></table></div><div class="p5-empty" data-p5-no-match hidden>এই নামে কোনো Material নেই।</div><?php else: ?><div class="p5-empty">এই প্রকল্পে এখনো উপকরণ যোগ হয়নি।</div><?php endif; ?>
</section>
<section class="p5-surface"><div class="p5-section-heading"><div><span class="p5-overline">MOVEMENT LOG</span><h2>সাম্প্রতিক Stock History</h2><p>প্রবেশ ও ব্যবহারের প্রতিটি পরিবর্তনের হিসাব।</p></div></div><?php if($movements): ?><div class="p5-table-scroll"><table class="p5-table"><thead><tr><th>তারিখ</th><th>উপকরণ</th><th>ধরন</th><th>পরিমাণ</th><th>কার মাধ্যমে</th><th>নোট</th></tr></thead><tbody><?php foreach($movements as $m): ?><tr><td><?= e($m['movement_date']) ?></td><td><?= e($m['item_name']) ?></td><td><span class="p5-pill <?= $m['movement_type']==='in'?'success':'warning' ?>"><?= $m['movement_type']==='in'?'Stock In':'Stock Out' ?></span></td><td><?= p5_qty_view($m['quantity']) ?> <?= e($m['unit']) ?></td><td><?= e($m['actor']) ?></td><td><?= e($m['note']??'—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="p5-empty">কোনো Stock Movement নেই।</div><?php endif; ?></section>
<p class="p5-note">Supplier Purchase Save করলে স্বয়ংক্রিয়ভাবে Stock In হবে। একই ক্রয় আবার Manual Stock In করবেন না। Manual Stock In/Out আর্থিক ব্যয়ের হিসাব পরিবর্তন করে না।</p>
</div><script src="assets/phase5.js?v=5.0.0" defer></script>
<?php include 'layout_bottom.php'; ?>
