<?php
declare(strict_types=1);
/** Phase 5 shared helpers. Does not replace existing config.php / authorization rules. */
require_once __DIR__ . '/config.php';

function p5_ready(): bool {
    static $ready = null;
    if ($ready === null) {
        try {
            foreach (['project_budgets','inventory_items','suppliers','supplier_purchases','inventory_movements','supplier_payments','audit_logs'] as $table) {
                // Fixed internal identifiers; no user input reaches table names.
                db()->query("SELECT 1 FROM `$table` LIMIT 0");
            }
            $ready = true;
        } catch (PDOException $ex) { $ready = false; }
    }
    return $ready;
}
function p5_require_ready(): void {
    if (p5_ready()) return;
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="bn"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Phase 5 Setup</title><body style="font:16px/1.7 sans-serif;max-width:640px;margin:9vh auto;padding:24px;color:#233049"><h1>Phase 5 Setup প্রয়োজন</h1><p>নতুন Module চালু করতে প্রথমে ডেটাবেসের ব্যাকআপ নিন এবং phpMyAdmin-এর <strong>jv_construction</strong> ডেটাবেসে <code>phase5_upgrade.sql</code> ফাইলটি Import করুন। পুরোনো কোনো SQL পুনরায় Import করবেন না।</p><p><a href="index.php">ড্যাশবোর্ডে ফিরুন</a></p></body></html>';
    exit;
}
function p5_admin(): void { if (!is_admin()) { http_response_code(403); exit('Admin access required.'); } }
function p5_get_string(string $key, int $max = 150): string {
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return '';
    $v = trim($v);
    return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : iconv_substr($v,0,$max,'UTF-8');
}
function p5_qty($v, bool $allowZero=false): ?string {
    if (!is_scalar($v) || !preg_match('/^\d{1,10}(?:\.\d{1,3})?$/D',(string)$v)) return null;
    $f = (float)$v;
    if (($allowZero ? $f < 0 : $f <= 0) || $f > 999999999.999) return null;
    return number_format($f,3,'.','');
}
function p5_money($v, bool $allowZero=false): ?string {
    if (!is_scalar($v) || !preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D',(string)$v)) return null;
    $f = (float)$v;
    if (($allowZero ? $f < 0 : $f <= 0) || $f > 999999999999.99) return null;
    return number_format($f,2,'.','');
}
function p5_bad_request(string $message): never { http_response_code(422); exit(e($message)); }
function p5_project(int $id, string $permission='view_project'): void {
    if ($id < 1 || !can('view_project',$id) || !can($permission,$id)) { http_response_code(403); exit('Project access denied.'); }
    $st = db()->prepare('SELECT id FROM projects WHERE id=?');
    $st->execute([$id]);
    if (!$st->fetchColumn()) p5_bad_request('Project not found.');
}
function p5_project_ids(string $permission='view_project'): array { return array_map('intval', array_column(allowed_projects($permission),'id')); }
function p5_project_where(string $column='p.id',string $permission='view_project'): string {
    $ids = p5_project_ids($permission);
    return $ids ? $column.' IN ('.implode(',',$ids).')' : '1=0';
}
function p5_audit(string $action,string $entity,?int $id,?int $project,string $detail,?int $actorId=null): void {
    $actor = $actorId ?? (isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null);
    $st=db()->prepare('INSERT INTO audit_logs(actor_id,action,entity_type,entity_id,project_id,description,ip_address) VALUES(?,?,?,?,?,?,?)');
    $safeDetail = function_exists('mb_substr') ? mb_substr($detail,0,500,'UTF-8') : iconv_substr($detail,0,500,'UTF-8');
    $st->execute([$actor,$action,$entity,$id,$project,$safeDetail,substr((string)($_SERVER['REMOTE_ADDR'] ?? ''),0,45)]);
}
function p5_audit_optional(string $action,string $entity,?int $id,?int $project,string $detail,?int $actorId=null): void {
    if (!p5_ready()) return; // Existing features continue to work before Phase-5 migration.
    try { p5_audit($action,$entity,$id,$project,$detail,$actorId); }
    catch (Throwable $ex) { error_log('JV Phase5 audit error: '.$ex->getMessage()); }
}
function p5_budget_rows(): array {
    $where = p5_project_where('p.id','view_report');
    $sql = "SELECT p.id,p.title,p.status,COALESCE(b.budget_amount,0) budget_amount,b.updated_at AS budget_updated,
      COALESCE(ex.total,0) expense_amount,COALESCE(pu.total,0) purchase_amount,
      COALESCE(pu.paid,0) supplier_paid
      FROM projects p
      LEFT JOIN project_budgets b ON b.project_id=p.id
      LEFT JOIN (SELECT project_id,SUM(amount) total FROM expenses WHERE status='approved' GROUP BY project_id) ex ON ex.project_id=p.id
      LEFT JOIN (SELECT x.project_id,SUM(x.total_amount) total,SUM(COALESCE(pay.paid,0)) paid
        FROM supplier_purchases x LEFT JOIN (SELECT purchase_id,SUM(amount) paid FROM supplier_payments GROUP BY purchase_id) pay ON pay.purchase_id=x.id GROUP BY x.project_id) pu ON pu.project_id=p.id
      WHERE $where ORDER BY p.title";
    return db()->query($sql)->fetchAll();
}
function p5_cash_view($amount): string { return '৳'.number_format((float)$amount,2); }
function p5_csv_cell($v): string {
    $s=(string)($v ?? '');
    // Prevent spreadsheet formula injection for text cells.
    if (preg_match('/^-?\d+(?:\.\d+)?$/D',$s)) return $s; // Preserve numeric cells including negative balances.
    return preg_match('/^[\s\x00-\x1f]*[=+\-@]/u',$s) ? "'".$s : $s;
}
function p5_csv(string $filename, array $head, array $rows): never {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: no-store');
    $o=fopen('php://output','wb'); fwrite($o,"\xEF\xBB\xBF");
    fputcsv($o,array_map('p5_csv_cell',$head));
    foreach ($rows as $row) fputcsv($o,array_map('p5_csv_cell',$row));
    fclose($o); exit;
}
function p5_selected_project(): int {
    $allowed = p5_project_ids();
    $requested = filter_input(INPUT_GET,'project_id',FILTER_VALIDATE_INT);
    $id = $requested ? (int)$requested : (int)($allowed[0] ?? 0);
    return in_array($id,$allowed,true) ? $id : 0;
}
function p5_amount_spent(array $row): float { return (float)$row['expense_amount'] + (float)$row['purchase_amount']; }
function p5_qty_view($n): string { return rtrim(rtrim(number_format((float)$n,3,'.',','),'0'),'.'); }
/** Transaction required. Keeps quantity changes and movement ledger in the same commit. */
function p5_stock_move(PDO $pdo,int $itemId,int $projectId,string $kind,string $qty,string $date,?int $purchaseId,string $note): void {
    if (!$pdo->inTransaction()) throw new LogicException('Stock movement requires a transaction.');
    if (!in_array($kind,['in','out'],true)) throw new InvalidArgumentException('Invalid movement.');
    $st=$pdo->prepare('SELECT i.project_id,i.quantity_on_hand,p.status FROM inventory_items i JOIN projects p ON p.id=i.project_id WHERE i.id=? FOR UPDATE');
    $st->execute([$itemId]);$item=$st->fetch();
    if (!$item || (int)$item['project_id']!==$projectId) throw new InvalidArgumentException('এই প্রকল্পে উপকরণ পাওয়া যায়নি।');
    if ($item['status']==='closed') throw new InvalidArgumentException('বন্ধ প্রকল্পে Stock পরিবর্তন করা যাবে না।');
    if ($kind==='in') {
        $st=$pdo->prepare('UPDATE inventory_items SET quantity_on_hand=quantity_on_hand+? WHERE id=? AND quantity_on_hand+?<=99999999999.999');
        $st->execute([$qty,$itemId,$qty]);
    } else {
        $st=$pdo->prepare('UPDATE inventory_items SET quantity_on_hand=quantity_on_hand-? WHERE id=? AND quantity_on_hand>=?');
        $st->execute([$qty,$itemId,$qty]);
    }
    if ($st->rowCount()!==1) throw new InvalidArgumentException($kind==='out'?'পর্যাপ্ত Stock নেই।':'Stock সীমা অতিক্রম করেছে।');
    $st=$pdo->prepare('INSERT INTO inventory_movements(item_id,movement_type,quantity,movement_date,purchase_id,note,created_by) VALUES(?,?,?,?,?,?,?)');
    $st->execute([$itemId,$kind,$qty,$date,$purchaseId,$note?:null,(int)user()['id']]);
}
