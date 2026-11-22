<?php
declare(strict_types=1);
session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'jv_construction';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) { header('Location: login.php'); exit; } }
function is_admin(): bool { return user() && user()['role'] === 'admin'; }
function valid_project_permission(string $permission): bool {
    return in_array($permission, [
        'view_project', 'add_expense', 'edit_expense', 'delete_expense',
        'add_head', 'view_report', 'approve_expense', 'close_project'
    ], true);
}
function can(string $permission, ?int $projectId = null): bool {
    if (!user() || !valid_project_permission($permission)) return false;
    if (is_admin()) return true;
    $sql = "SELECT {$permission} FROM project_assignments WHERE user_id=? AND project_id=?";
    $st = db()->prepare($sql);
    $st->execute([user()['id'], $projectId]);
    return (bool)$st->fetchColumn();
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); exit('Invalid request token.');
    }
}
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = [$message, $type]; }
function redirect(string $url): never { header("Location: $url"); exit; }

function allowed_projects(string $permission='view_project'): array {
 if (!user() || !valid_project_permission($permission)) return [];
 $pdo=db(); if(is_admin()) return $pdo->query("SELECT id,title,status FROM projects ORDER BY title")->fetchAll();
 $st=$pdo->prepare("SELECT p.id,p.title,p.status FROM projects p JOIN project_assignments a ON a.project_id=p.id WHERE a.user_id=? AND a.$permission=1 ORDER BY p.title");
 $st->execute([user()['id']]); return $st->fetchAll();
}
function valid_date(string $v): bool { $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d!==false && $d->format('Y-m-d')===$v; }
function positive_money($v): bool { return is_scalar($v) && is_numeric($v) && (float)$v>0 && (float)$v<=999999999999.99; }
