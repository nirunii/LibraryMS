<?php
// MODEL: audit log. Records who did what, so staff actions can be traced later.
class Audit extends Model
{
    // Call Audit::log('book.add', 'Clean Code') from any controller. It never breaks the page if logging fails.
    public static function log($action, $details = '')
    {
        try {
            $u = user();
            $st = Database::get()->prepare('INSERT INTO audit_log (user_id, user_name, action, details) VALUES (?, ?, ?, ?)');
            $st->execute([$u['id'] ?? null, $u['name'] ?? 'Guest', $action, substr((string)$details, 0, 255)]);
        } catch (Throwable $e) {
            // ignore logging problems
        }
    }

    public function recent($action = '', $limit = 200)
    {
        $limit = (int)$limit;
        if ($action !== '') {
            return $this->many("SELECT * FROM audit_log WHERE action = ? ORDER BY id DESC LIMIT $limit", [$action]);
        }
        return $this->many("SELECT * FROM audit_log ORDER BY id DESC LIMIT $limit");
    }

    public function actions()
    {
        return $this->db->query('SELECT DISTINCT action FROM audit_log ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
    }
}
