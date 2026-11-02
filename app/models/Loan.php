<?php
// MODEL: loans. Contains the borrowing, returning and fine rules.
class Loan extends Model
{
    public function calculateFine($dueDate, $onDate = null)
    {
        $on = $onDate ? strtotime($onDate) : strtotime(date('Y-m-d'));
        $days = (int)floor(($on - strtotime($dueDate)) / 86400);
        return $days > 0 ? $days * FINE_PER_DAY : 0;
    }

    // Returns [success(bool), message(string)]
    public function borrow($userId, $bookId)
    {
        $this->db->beginTransaction();
        try {
            $book = $this->one('SELECT * FROM books WHERE id = ? FOR UPDATE', [$bookId]);
            $error = null;
            if (!$book) {
                $error = 'Book not found.';
            } elseif ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND returned_at IS NULL', [$userId]) >= MAX_LOANS) {
                $error = 'Borrow limit reached (' . MAX_LOANS . ' books).';
            } elseif ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND returned_at IS NULL AND due_date < CURDATE()', [$userId]) > 0) {
                $error = 'Return your overdue books before borrowing more.';
            } elseif ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND book_id = ? AND returned_at IS NULL', [$userId, $bookId]) > 0) {
                $error = 'You already have this book on loan.';
            } else {
                $reservedByOthers = (int)$this->scalar("SELECT COUNT(*) FROM reservations WHERE book_id = ? AND status = 'active' AND user_id <> ?", [$bookId, $userId]);
                if ($book['copies_available'] - $reservedByOthers <= 0) {
                    $error = 'No copy is available right now. You can reserve it.';
                }
            }
            if ($error) {
                $this->db->rollBack();
                return [false, $error];
            }
            $due = date('Y-m-d', strtotime('+' . LOAN_DAYS . ' days'));
            $this->db->prepare('INSERT INTO loans (book_id, user_id, borrowed_at, due_date) VALUES (?, ?, NOW(), ?)')->execute([$bookId, $userId, $due]);
            $this->db->prepare('UPDATE books SET copies_available = copies_available - 1 WHERE id = ?')->execute([$bookId]);
            $this->db->prepare("UPDATE reservations SET status = 'fulfilled' WHERE book_id = ? AND user_id = ? AND status = 'active'")->execute([$bookId, $userId]);
            $this->db->commit();
            return [true, 'Book issued. Due date: ' . $due . '.'];
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return [false, 'Something went wrong while borrowing.'];
        }
    }

    // Returns [success, message]
    public function returnBook($loanId)
    {
        $this->db->beginTransaction();
        try {
            $loan = $this->one('SELECT * FROM loans WHERE id = ? AND returned_at IS NULL FOR UPDATE', [$loanId]);
            if (!$loan) {
                $this->db->rollBack();
                return [false, 'Loan not found or already returned.'];
            }
            $fine = $this->calculateFine($loan['due_date']);
            $this->db->prepare('UPDATE loans SET returned_at = NOW(), fine_amount = ? WHERE id = ?')->execute([$fine, $loanId]);
            $this->db->prepare('UPDATE books SET copies_available = copies_available + 1 WHERE id = ?')->execute([$loan['book_id']]);
            $this->db->commit();
            return [true, $fine > 0 ? 'Book returned. Fine to collect: ' . money($fine) . '.' : 'Book returned. No fine.'];
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return [false, 'Something went wrong while returning.'];
        }
    }

    private function withFines($rows)
    {
        foreach ($rows as &$r) {
            $r['fine'] = $r['returned_at'] ? (float)$r['fine_amount'] : $this->calculateFine($r['due_date']);
            $r['overdue'] = !$r['returned_at'] && $r['due_date'] < date('Y-m-d');
            $r['days_overdue'] = $r['overdue'] ? (int)floor((strtotime(date('Y-m-d')) - strtotime($r['due_date'])) / 86400) : 0;
        }
        return $rows;
    }

    public function active()
    {
        return $this->withFines($this->many(
            'SELECT l.*, b.title, u.name AS member FROM loans l
             JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id
             WHERE l.returned_at IS NULL ORDER BY l.due_date'
        ));
    }

    public function byUser($userId)
    {
        return $this->withFines($this->many(
            'SELECT l.*, b.title, u.name AS member FROM loans l
             JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id
             WHERE l.user_id = ? ORDER BY l.returned_at IS NOT NULL, l.borrowed_at DESC',
            [$userId]
        ));
    }

    // A member's loans that have not been returned yet.
    public function activeByUser($userId)
    {
        return array_values(array_filter($this->byUser($userId), function ($r) { return !$r['returned_at']; }));
    }

    public function overdue()
    {
        return array_values(array_filter($this->active(), function ($r) { return $r['overdue']; }));
    }

    public function countActive($userId = null)
    {
        if ($userId) {
            return (int)$this->scalar('SELECT COUNT(*) FROM loans WHERE returned_at IS NULL AND user_id = ?', [$userId]);
        }
        return (int)$this->scalar('SELECT COUNT(*) FROM loans WHERE returned_at IS NULL');
    }

    public function countOverdue()
    {
        return (int)$this->scalar('SELECT COUNT(*) FROM loans WHERE returned_at IS NULL AND due_date < CURDATE()');
    }

    public function finesCollected()
    {
        return (float)$this->scalar('SELECT COALESCE(SUM(fine_amount), 0) FROM loans WHERE returned_at IS NOT NULL');
    }

    // Loans per month for the last 6 months (dashboard chart).
    // Pass a user id to count only that member's loans; leave null for the whole library.
    public function monthly($userId = null)
    {
        $sql = "SELECT DATE_FORMAT(borrowed_at, '%Y-%m') AS ym, COUNT(*) AS total FROM loans
                WHERE borrowed_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)";
        $params = [];
        if ($userId) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        $found = [];
        foreach ($this->many($sql . ' GROUP BY ym', $params) as $r) {
            $found[$r['ym']] = (int)$r['total'];
        }
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $t = strtotime(date('Y-m-01') . " -$i months");
            $months[] = ['label' => date('M', $t), 'total' => $found[date('Y-m', $t)] ?? 0];
        }
        return $months;
    }
}
