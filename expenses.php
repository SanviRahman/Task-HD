<?php
require_once 'config.php';require_login();$pdo=db();$projects=allowed_projects('view_project');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$pid=(int)($_POST['project_id']??0);$hid=(int)($_POST['head_id']??0);$employee=(int)($_POST['advance_employee_id']??0);$date=(string)($_POST['entry_date']??'');$amount=$_POST['amount']??0;$action=$_POST['action']??'add';
 if(!can($action==='edit'?'edit_expense':'add_expense',$pid)||!valid_date($date)||!positive_money($amount)){http_response_code(403);exit('Invalid permission, date or amount');}
 $s=$pdo->prepare("SELECT h.id FROM expense_heads h JOIN projects p ON p.id=h.project_id WHERE h.id=? AND h.project_id=? AND p.status!='closed'");$s->execute([$hid,$pid]);if(!$s->fetchColumn())exit('Invalid expense head or closed project');
 if($employee){$s=$pdo->prepare('SELECT id FROM users WHERE id=? AND active=1');$s->execute([$employee]);if(!$s->fetchColumn())exit('Invalid employee');}
 if($employee){$assignment=$pdo->prepare('SELECT 1 FROM project_assignments WHERE project_id=? AND user_id=?');$assignment->execute([$pid,$employee]);if(!$assignment->fetchColumn()){http_response_code(403);exit('Employee is not assigned to this project.');}}
if($action==='edit'){
  $id=(int)($_POST['id']??0);$s=$pdo->prepare('SELECT project_id FROM expenses WHERE id=?');$s->execute([$id]);$old=$s->fetch();if(!$old||!can('edit_expense',(int)$old['project_id'])){http_response_code(403);exit('Edit denied');}
  $s=$pdo->prepare("UPDATE expenses SET project_id=?,head_id=?,entry_date=?,amount=?,description=?,advance_employee_id=?,status='pending',approved_by=NULL WHERE id=?");$s->execute([$pid,$hid,$date,$amount,trim($_POST['description']??''),$employee?:null,$id]);
 }else{$s=$pdo->prepare('INSERT INTO expenses(project_id,head_id,entry_date,amount,description,entered_by,advance_employee_id) VALUES(?,?,?,?,?,?,?)');$s->execute([$pid,$hid,$date,$amount,trim($_POST['description']??''),user()['id'],$employee?:null]);}
 require_once __DIR__.'/phase5_lib.php';p5_audit_optional($action==='edit'?'expense.edited':'expense.created','expenses',(int)($action==='edit'?$id:$pdo->lastInsertId()),$pid,'Expense saved (pending approval)');
 flash('Expense saved and awaiting approval.');redirect('expenses.php');
}
if(is_admin())$rows=$pdo->query('SELECT e.*,p.title project_title,h.name head_name,u.name user_name FROM expenses e JOIN projects p ON p.id=e.project_id JOIN expense_heads h ON h.id=e.head_id JOIN users u ON u.id=e.entered_by ORDER BY e.entry_date DESC,e.id DESC LIMIT 500')->fetchAll();
else{$s=$pdo->prepare('SELECT e.*,p.title project_title,h.name head_name,u.name user_name FROM expenses e JOIN projects p ON p.id=e.project_id JOIN expense_heads h ON h.id=e.head_id JOIN users u ON u.id=e.entered_by JOIN project_assignments a ON a.project_id=p.id WHERE a.user_id=? AND a.view_project=1 ORDER BY e.entry_date DESC,e.id DESC LIMIT 500');$s->execute([user()['id']]);$rows=$s->fetchAll();}
$heads=$pdo->query('SELECT id,project_id,name FROM expense_heads ORDER BY name')->fetchAll();$employees=$pdo->query('SELECT id,name FROM users WHERE active=1 ORDER BY name')->fetchAll();
$expensePending = count(array_filter($rows, fn($r) => $r['status'] === 'pending'));
$expenseApproved = count(array_filter($rows, fn($r) => $r['status'] === 'approved'));
$expenseApprovedTotal = array_reduce($rows, fn($total, $r) => $total + ($r['status'] === 'approved' ? (float) $r['amount'] : 0), 0.0);
$expenseStatusLabels = ['pending' => 'অপেক্ষমাণ', 'approved' => 'অনুমোদিত', 'rejected' => 'বাতিল'];
$formProjects = array_values(array_filter($projects, fn($p) => $p['status'] !== 'closed' && (can('add_expense', (int) $p['id']) || can('edit_expense', (int) $p['id']))));
require_once __DIR__.'/phase5_lib.php';
$projectBudgetInfo = [];
if (p5_ready()) {
    foreach (p5_budget_rows() as $budgetRow) {
        $amount = (float)$budgetRow['budget_amount'];
        $projectBudgetInfo[(int)$budgetRow['id']] = [
            'budget' => $amount > 0 ? p5_cash_view($amount) : '',
            'remaining' => $amount > 0 ? p5_cash_view($amount - p5_amount_spent($budgetRow)) : ''
        ];
    }
}
include 'layout_top.php';?>
<div class="records-page records-expenses" data-records-page="expenses">
    <section class="records-intro" aria-labelledby="expenses-heading"><div class="records-intro-copy"><span class="records-kicker"><svg class="ui-icon" aria-hidden="true"><use href="#jv-receipt"></use></svg> FINANCE WORKSPACE <span class="records-kicker-dot"></span> 02 / EXPENSES</span><h1 id="expenses-heading">ব্যয়ের প্রতিটি তথ্য, <em>পরিষ্কার হিসাব।</em></h1><p>দৈনিক ব্যয় লিখুন, প্রকল্প অনুযায়ী খাত বাছুন এবং অনুমোদনের অবস্থা সহজেই দেখুন।</p><a class="record-btn record-btn-primary records-intro-action" href="#expense-entry"><svg class="ui-icon" aria-hidden="true"><use href="#jv-plus"></use></svg> নতুন ব্যয় এন্ট্রি</a></div><div class="intro-decor intro-decor-expenses" aria-hidden="true"><span class="intro-illustration"><svg class="ui-icon"><use href="#jv-receipt"></use></svg></span><span class="intro-decor-dots"></span></div></section>

    <section class="records-metrics records-metrics-four" aria-label="সাম্প্রতিক ব্যয়ের সারসংক্ষেপ">
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-purple"><svg class="ui-icon" aria-hidden="true"><use href="#jv-clipboard"></use></svg></span><span>এন্ট্রি</span></div><strong><?= number_format(count($rows)) ?></strong><small>তালিকায় প্রদর্শিত ব্যয়</small></article>
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-coral"><svg class="ui-icon" aria-hidden="true"><use href="#jv-clock"></use></svg></span><span>অপেক্ষমাণ</span></div><strong><?= number_format($expensePending) ?></strong><small>অনুমোদনের অপেক্ষায়</small></article>
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-teal"><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg></span><span>অনুমোদিত</span></div><strong><?= number_format($expenseApproved) ?></strong><small>তালিকায় অনুমোদিত এন্ট্রি</small></article>
        <article class="record-metric"><div class="metric-heading"><span class="mini-icon mini-blue"><svg class="ui-icon" aria-hidden="true"><use href="#jv-wallet"></use></svg></span><span>অনুমোদিত মোট</span></div><strong class="metric-currency">৳<?= number_format($expenseApprovedTotal, 2) ?></strong><small>প্রদর্শিত এন্ট্রিগুলোর যোগফল</small></article>
    </section>
    <p class="records-scope-note">সারসংক্ষেপ ও Search/Filter সর্বশেষ সর্বোচ্চ ৫০০টি প্রদর্শিত ব্যয়ের উপর প্রযোজ্য।</p>

    <section class="record-surface" id="expense-entry" aria-labelledby="expense-form-heading">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">01</span><div><span class="surface-kicker">NEW TRANSACTION</span><h2 id="expense-form-heading" data-form-title>ব্যয় এন্ট্রি করুন</h2><p>তারিখ, খাত ও টাকার পরিমাণ ঠিকভাবে পূরণ করুন।</p></div></div><span class="surface-header-tag" data-form-badge>নতুন এন্ট্রি</span></div>
        <form method="post" class="record-form" id="expenseForm" data-edit-form="expense">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" id="action" name="action" value="add"><input type="hidden" id="rid" name="id" value="">
            <div class="record-field"><label for="pid">প্রকল্প <span class="required" aria-label="required">*</span></label><select id="pid" name="project_id" required><option value="" selected disabled>প্রকল্প নির্বাচন করুন</option><?php foreach ($formProjects as $p): ?><option value="<?= (int) $p['id'] ?>" data-can-add="<?= can('add_expense', (int) $p['id']) ? '1' : '0' ?>" data-can-edit="<?= can('edit_expense', (int) $p['id']) ? '1' : '0' ?>" data-budget-access="<?= isset($projectBudgetInfo[(int)$p['id']]) ? '1' : '0' ?>" data-project-budget="<?= e($projectBudgetInfo[(int)$p['id']]['budget'] ?? '') ?>" data-project-remaining="<?= e($projectBudgetInfo[(int)$p['id']]['remaining'] ?? '') ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></div>
            <div class="record-field"><label for="hid">ব্যয়ের খাত <span class="required" aria-label="required">*</span></label><select id="hid" name="head_id" required><option value="" selected disabled>প্রথমে প্রকল্প নির্বাচন করুন</option><?php foreach ($heads as $h): ?><option data-project="<?= (int) $h['project_id'] ?>" value="<?= (int) $h['id'] ?>" hidden disabled><?= e($h['name']) ?></option><?php endforeach; ?></select><span class="field-hint" id="expense-head-hint" role="status">প্রকল্প অনুযায়ী ব্যয়ের খাত দেখাবে।</span></div>
            <div class="record-field"><label for="emp">অগ্রিম গ্রহণকারী কর্মী <span class="optional">(ঐচ্ছিক)</span></label><select id="emp" name="advance_employee_id"><option value="0">সাধারণ প্রকল্প ব্যয়</option><?php foreach ($employees as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?></option><?php endforeach; ?></select><span class="field-hint">কর্মীর অগ্রিম থেকে খরচ হলে সেই প্রকল্পে নির্ধারিত কর্মী বাছুন।</span></div>
            <div class="record-budget-overview record-col-full" role="group" aria-label="Project budget summary">
                <div class="record-field"><label for="expenseProjectBudget">মোট নির্ধারিত বাজেট</label><input id="expenseProjectBudget" type="text" readonly aria-readonly="true" placeholder="প্রকল্প নির্বাচন করুন" tabindex="0"></div>
                <div class="record-field"><label for="expenseProjectRemaining">অবশিষ্ট</label><input id="expenseProjectRemaining" type="text" readonly aria-readonly="true" placeholder="প্রকল্প নির্বাচন করুন" tabindex="0"></div>
            </div>
            <div class="record-field"><label for="dt">ব্যয়ের তারিখ <span class="required" aria-label="required">*</span></label><input id="dt" type="date" name="entry_date" value="<?= date('Y-m-d') ?>" placeholder="দিন / মাস / বছর" required></div>
            <div class="record-field"><label for="amt">ব্যয়ের পরিমাণ (৳) <span class="required" aria-label="required">*</span></label><input id="amt" type="number" min="0.01" step="0.01" max="999999999999.99" name="amount" placeholder="যেমন: 12500.00" inputmode="decimal" required></div>
            <div class="record-field record-col-full"><label for="desc">ব্যয়ের বিবরণ <span class="optional">(ঐচ্ছিক)</span></label><input id="desc" name="description" type="text" placeholder="যেমন: সিমেন্ট ও বালু পরিবহন খরচ" autocomplete="off"></div>
            <div class="record-form-actions"><p><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg> সংরক্ষণের পর ব্যয়টি অনুমোদনের অপেক্ষায় থাকবে।</p><div class="record-form-buttons"><button class="record-btn record-btn-secondary" type="button" id="cancelExpenseEdit" hidden>সংশোধন বাতিল</button><button class="record-btn record-btn-primary" type="submit" id="expenseSaveButton"><svg class="ui-icon" aria-hidden="true"><use href="#jv-check"></use></svg> ব্যয় সংরক্ষণ</button></div></div>
        </form>
        <?php if (!$formProjects): ?><div class="record-callout record-callout-under" role="status">ব্যয় যোগ করার উপযোগী কোনো খোলা প্রকল্প পাওয়া যায়নি। <?php if (is_admin()): ?><a href="projects.php">প্রকল্পসমূহ দেখুন</a><?php endif; ?></div><?php endif; ?>
    </section>

    <section class="record-surface" id="expense-list" aria-labelledby="expense-list-heading">
        <div class="record-surface-header"><div class="surface-header-title"><span class="surface-step">02</span><div><span class="surface-kicker">TRANSACTION HISTORY</span><h2 id="expense-list-heading">ব্যয়ের তালিকা</h2><p>ব্যয় খুঁজুন, অবস্থা দেখুন এবং অনুমোদন/সংশোধন করুন।</p></div></div><span class="record-count"><?= number_format(count($rows)) ?> এন্ট্রি</span></div>
        <?php if ($rows): ?>
            <div class="records-toolbar" data-record-toolbar hidden>
                <div class="record-search"><svg class="ui-icon" aria-hidden="true"><use href="#jv-grid"></use></svg><label class="sr-only" for="expenseSearch">ব্যয় খুঁজুন</label><input type="search" id="expenseSearch" data-search-input placeholder="প্রকল্প, খাত বা বিবরণ দিয়ে খুঁজুন..."></div>
                <label class="record-select-wrap"><span>প্রকল্প</span><select data-filter="project" aria-label="প্রকল্প"><option value="">সব প্রকল্প</option><?php foreach ($projects as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></label>
                <label class="record-select-wrap"><span>অবস্থা</span><select data-filter="status" aria-label="ব্যয়ের অবস্থা"><option value="">সব অবস্থা</option><option value="pending">অপেক্ষমাণ</option><option value="approved">অনুমোদিত</option><option value="rejected">বাতিল</option></select></label>
                <button type="button" class="record-clear" data-clear-filters>Reset</button>
            </div>
            <div class="records-table-wrap"><table class="records-table" data-record-table><thead><tr><th scope="col">তারিখ</th><th scope="col">প্রকল্প / খাত</th><th scope="col">টাকা</th><th scope="col">বিবরণ</th><th scope="col">অবস্থা</th><th scope="col">অ্যাকশন</th></tr></thead><tbody>
                <?php foreach ($rows as $r): ?>
                <tr data-record-row data-project="<?= (int) $r['project_id'] ?>" data-status="<?= e($r['status']) ?>">
                    <td data-label="তারিখ"><time datetime="<?= e($r['entry_date']) ?>"><?= e($r['entry_date']) ?></time></td>
                    <td data-label="প্রকল্প / খাত"><span class="record-main"><span class="table-record-icon table-icon-receipt"><svg class="ui-icon" aria-hidden="true"><use href="#jv-receipt"></use></svg></span><span><strong><?= e($r['project_title']) ?></strong><small><?= e($r['head_name']) ?></small></span></span></td>
                    <td data-label="টাকা"><strong class="table-amount">৳<?= number_format((float) $r['amount'], 2) ?></strong></td>
                    <td data-label="বিবরণ"><span class="record-description" title="<?= e($r['description'] ?? '') ?>"><?= e($r['description'] ?: '—') ?></span></td>
                    <td data-label="অবস্থা"><span class="record-status <?= e($r['status']) ?>"><span class="status-dot" aria-hidden="true"></span><?= e($expenseStatusLabels[$r['status']] ?? ucfirst($r['status'])) ?></span></td>
                    <td data-label="অ্যাকশন"><div class="record-row-actions">
                        <?php if (can('edit_expense', (int) $r['project_id'])): ?><button type="button" class="record-btn record-btn-small record-btn-secondary" onclick='editExpense(<?= e(json_encode(['id' => $r['id'], 'project_id' => $r['project_id'], 'head_id' => $r['head_id'], 'advance_employee_id' => $r['advance_employee_id'], 'entry_date' => $r['entry_date'], 'amount' => $r['amount'], 'description' => $r['description']], JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)'>সংশোধন</button><?php endif; ?>
                        <?php if (is_admin() && $r['status'] === 'pending'): ?><form method="post" action="approve_expense.php" class="record-inline-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button type="submit" class="record-btn record-btn-small record-btn-approve" data-confirm="এই ব্যয়টি অনুমোদন করতে চান?">অনুমোদন</button></form><?php endif; ?>
                        <?php if (!can('edit_expense', (int) $r['project_id']) && !(is_admin() && $r['status'] === 'pending')): ?><span class="record-no-action">—</span><?php endif; ?>
                    </div></td>
                </tr>
                <?php endforeach; ?>
            </tbody></table></div>
            <div class="record-no-match" data-no-results hidden><svg class="ui-icon" aria-hidden="true"><use href="#jv-grid"></use></svg><strong>কোনো ব্যয় পাওয়া যায়নি</strong><span>অন্য শব্দ দিয়ে খুঁজুন অথবা Filter Reset করুন।</span></div>
            <div class="records-pagination" data-record-pagination hidden><span data-result-count aria-live="polite"></span><div class="pager-controls"><label>প্রতি পেজে <select data-page-size aria-label="প্রতি পেজে কতটি রেকর্ড"><option value="10">10</option><option value="20">20</option><option value="50">50</option></select></label><button type="button" data-page-prev aria-label="আগের পেজ">‹</button><span data-page-info></span><button type="button" data-page-next aria-label="পরের পেজ">›</button></div></div>
        <?php else: ?>
            <div class="record-empty"><span class="record-empty-icon"><svg class="ui-icon" aria-hidden="true"><use href="#jv-receipt"></use></svg></span><strong>এখনো কোনো ব্যয় এন্ট্রি নেই</strong><p>প্রকল্প নির্বাচন করে প্রথম ব্যয়ের তথ্য যোগ করুন।</p><a href="#expense-entry" class="record-btn record-btn-secondary">ব্যয় যোগ করুন</a></div>
        <?php endif; ?>
    </section>
</div>
<script src="assets/records-ui.js?v=1.1.0" defer></script>
<?php include 'layout_bottom.php'; ?>
