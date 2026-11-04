<?php
// MODEL: holds queue. A member who wants a book with no free copy joins the queue ("active").
// When a copy is returned the first member in line becomes "ready": the copy is held for them
// until expires_on. If they do not collect it in time the hold expires and the next member is served.
class Reservation extends Model
{
    // Moves expired holds out of the way and serves waiting members when copies are free.
    public function refresh()
    {
        $this->db->exec("UPDATE reservations SET status = 'expired' WHERE status = 'ready' AND expires_on < CURDATE()");
        foreach ($this->many("SELECT DISTINCT book_id FROM reservations WHERE status = 'active'") as $r) {
            $this->promote((int)$r['book_id']);
        }
    }

    // Makes the next waiting member(s) "ready" while there are free copies. Returns how many were promoted.
    public function promote($bookId)
    {
        $promoted = 0;
        $days = (int)Setting::get('hold_pickup_days');
        while (true) {
            $book = $this->one('SELECT copies_available FROM books WHERE id = ?', [$bookId]);
            if (!$book) { break; }
            $ready = (int)$this->scalar("SELECT COUNT(*) FROM reservations WHERE book_id = ? AND status = 'ready'", [$bookId]);
            if ((int)$book['copies_available'] - $ready <= 0) { break; }
            $next = $this->one("SELECT id FROM reservations WHERE book_id = ? AND status = 'active' ORDER BY reserved_at, id LIMIT 1", [$bookId]);
            if (!$next) { break; }
            $this->db->prepare("UPDATE reservations SET status = 'ready', ready_at = NOW(), expires_on = ? WHERE id = ?")
                ->execute([date('Y-m-d', strtotime('+' . $days . ' days')), $next['id']]);
            $promoted++;
        }
        return $promoted;
    }

    // Returns [success, message]
    public function reserve($userId, $bookId)
    {
        $book = $this->one('SELECT * FROM books WHERE id = ?', [$bookId]);
        if (!$book) { return [false, 'Book not found.']; }
        $held = (int)$this->scalar("SELECT COUNT(*) FROM reservations WHERE book_id = ? AND status = 'ready'", [$bookId]);
        if ((int)$book['copies_available'] - $held > 0) { return [false, 'A copy is available. Borrow it instead.']; }
        if ($this->scalar("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND book_id = ? AND status IN ('active', 'ready')", [$userId, $bookId]) > 0) {
            return [false, 'You already have a reservation for this book.'];
        }
        if ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND book_id = ? AND returned_at IS NULL', [$userId, $bookId]) > 0) {
            return [false, 'You already have this book on loan.'];
        }
        $this->db->prepare('INSERT INTO reservations (book_id, user_id) VALUES (?, ?)')->execute([$bookId, $userId]);
        return [true, 'Book reserved. We will hold a copy for you when it is your turn.'];
    }

    // Cancels a hold. Pass $userId so members can only cancel their own; staff pass null. Returns true if cancelled.
    public function cancel($id, $userId = null)
    {
        $sql = "SELECT * FROM reservations WHERE id = ? AND status IN ('active', 'ready')";
        $p = [$id];
        if ($userId !== null) { $sql .= ' AND user_id = ?'; $p[] = $userId; }
        $r = $this->one($sql, $p);
        if (!$r) { return false; }
        $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        $this->promote((int)$r['book_id']);
        return true;
    }

    private function positionSql()
    {
        return "(SELECT COUNT(*) FROM reservations r2 WHERE r2.book_id = r.book_id AND r2.status IN ('active', 'ready')
                 AND (r2.reserved_at < r.reserved_at OR (r2.reserved_at = r.reserved_at AND r2.id < r.id))) + 1";
    }

    // A member's open holds with their place in the queue.
    public function byUser($userId)
    {
        return $this->many(
            'SELECT r.*, b.title, ' . $this->positionSql() . " AS place
             FROM reservations r JOIN books b ON b.id = r.book_id
             WHERE r.user_id = ? AND r.status IN ('active', 'ready') ORDER BY r.reserved_at",
            [$userId]
        );
    }

    // Every open hold in the library (for staff).
    public function queue()
    {
        return $this->many(
            'SELECT r.*, b.title, u.name AS member, ' . $this->positionSql() . " AS place
             FROM reservations r JOIN books b ON b.id = r.book_id JOIN users u ON u.id = r.user_id
             WHERE r.status IN ('active', 'ready') ORDER BY b.title, place"
        );
    }

    // Book ids that currently have a copy held for this member.
    public function readyBookIds($userId)
    {
        $st = $this->db->prepare("SELECT book_id FROM reservations WHERE user_id = ? AND status = 'ready'");
        $st->execute([$userId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }
}
