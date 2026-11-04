<?php
// MODEL: loans. Contains the borrowing, renewing, returning and fine rules.
class Loan extends Model
{
    public function calculateFine($dueDate, $onDate = null)
    {
        $on = $onDate ? strtotime($onDate) : strtotime(date('Y-m-d'));
        $days = (int)floor(($on - strtotime($dueDate)) / 86400);
        return $days > 0 ? $days * Setting::get('fine_per_day') : 0;
    }

    // Returns [success(bool), message(string)]
    public function borrow($userId, $bookId)
    {
        $this->db->beginTransaction();
        try {
            $book = $this->one('SELECT * FROM books WHERE id = ? FOR UPDATE', [$bookId]);
            $member = $this->one('SELECT status FROM users WHERE id = ?', [$userId]);
            $maxLoans = Setting::get('max_loans');
            $blockLimit = Setting::get('fine_block_limit');
            $error = null;
            if (!$book) {
                $error = 'Book not found.';
            } elseif (!$member) {
                $error = 'Member not found.';
            } elseif ($member['status'] !== 'active') {
                $error = 'This membership is suspended. Please speak to the library staff.';
            } elseif ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND returned_at IS NULL', [$userId]) >= $maxLoans) {
                $error = 'Borrow limit reached (' . $maxLoans . ' books).';
            } elseif ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND returned_at IS NULL AND due_date < CURDATE()', [$userId]) > 0) {
                $error = 'Return your overdue books before borrowing more.';
            } elseif ((new Payment())->outstandingForUser($userId) > $blockLimit) {
                $error = 'Unpaid fines are above ' . money($blockLimit) . '. Please pay at the desk before borrowing.';
            } elseif ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND book_id = ? AND returned_at IS NULL', [$userId, $bookId]) > 0) {
                $error = 'You already have this book on loan.';
            } else {
                // copies that are being held for other members cannot be borrowed
                $heldForOthers = (int)$this->scalar("SELECT COUNT(*) FROM reservations WHERE book_id = ? AND status = 'ready' AND user_id <> ?", [$bookId, $userId]);
                if ($book['copies_available'] - $heldForOthers <= 0) {
                    $error = 'No copy is available right now. You can reserve it.';
                }
            }
            if ($error) {
                $this->db->rollBack();
                return [false, $error];
            }
            $due = date('Y-m-d', strtotime('+' . Setting::get('loan_days') . ' days'));
            $this->db->prepare('INSERT INTO loans (book_id, user_id, borrowed_at, due_date) VALUES (?, ?, NOW(), ?)')->execute([$bookId, $userId, $due]);
            $this->db->prepare('UPDATE books SET copies_available = copies_available - 1 WHERE id = ?')->execute([$bookId]);
            $this->db->prepare("UPDATE reservations SET status = 'fulfilled' WHERE book_id = ? AND user_id = ? AND status IN ('active', 'ready')")->execute([$bookId, $userId]);
            $this->db->commit();
            return [true, 'Book issued. Due date: ' . $due . '.'];
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return [false, 'Something went wrong while borrowing.'];
        }
    }

    // Extends the due date. Members may renew their own loans; staff may renew any loan.
    // Returns [success, message]
    public function renew($loanId, $actorId, $actorIsStaff)
    {
        $loan = $this->one('SELECT * FROM loans WHERE id = ? AND returned_at IS NULL', [$loanId]);
        if (!$loan) { return [false, 'Loan not found or already returned.']; }
        if (!$actorIsStaff && (int)$loan['user_id'] !== (int)$actorId) { return [false, 'You can only renew your own loans.']; }
        if ($loan['due_date'] < date('Y-m-d')) { return [false, 'Overdue books cannot be renewed. Return the book and pay any fine.']; }
        $max = Setting::get('max_renewals');
        if ((int)$loan['renewals'] >= $max) { return [false, 'This loan has reached the renewal limit (' . $max . ').']; }
        $waiting = (int)$this->scalar("SELECT COUNT(*) FROM reservations WHERE book_id = ? AND status IN ('active', 'ready') AND user_id <> ?", [$loan['book_id'], $loan['user_id']]);
        if ($waiting > 0) { return [false, 'Another member is waiting for this book, so it cannot be renewed.']; }
        $new = date('Y-m-d', strtotime($loan['due_date'] . ' +' . Setting::get('renew_days') . ' days'));
        $this->db->prepare('UPDATE loans SET due_date = ?, renewals = renewals + 1 WHERE id = ?')->execute([$new, $loanId]);
        return [true, 'Loan renewed. New due date: ' . $new . '.'];
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
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return [false, 'Something went wrong while returning.'];
        }
        // serve the next member in the holds queue (a problem here must not undo the return)
        $promoted = 0;
        try { $promoted = (new Reservation())->promote((int)$loan['book_id']); } catch (Throwable $ex) { }
        $msg = $fine > 0 ? 'Book returned. Fine to collect: ' . money($fine) . '.' : 'Book returned. No fine.';
        if ($promoted > 0) { $msg .= ' A copy is now held for the next member in the queue.'; }
        return [true, $msg];
    }

    private function withFines($rows)
    {
        $maxRenewals = Setting::get('max_renewals');
        $held = array_map('intval', $this->db->query("SELECT DISTINCT book_id FROM reservations WHERE status IN ('active', 'ready')")->fetchAll(PDO::FETCH_COLUMN));
        $today = date('Y-m-d');
        foreach ($rows as &$r) {
            $r['fine'] = $r['returned_at'] ? (float)$r['fine_amount'] : $this->calculateFine($r['due_date']);
            $r['overdue'] = !$r['returned_at'] && $r['due_date'] < $today;
            $r['days_overdue'] = $r['overdue'] ? (int)floor((strtotime($today) - strtotime($r['due_date'])) / 86400) : 0;
            $r['paid'] = isset($r['paid']) ? (float)$r['paid'] : 0.0;
            $r['balance'] = $r['returned_at'] ? max(0, round((float)$r['fine_amount'] - $r['paid'], 2)) : 0;
            $r['can_renew'] = !$r['returned_at'] && !$r['overdue'] && (int)$r['renewals'] < $maxRenewals && !in_array((int)$r['book_id'], $held, true);
        }
        unset($r);
        return $rows;
    }

    private function paidSql()
    {
        return '(SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.loan_id = l.id) AS paid';
    }

    public function active()
    {
        return $this->withFines($this->many(
            'SELECT l.*, b.title, u.name AS member, ' . $this->paidSql() . ' FROM loans l
             JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id
             WHERE l.returned_at IS NULL ORDER BY l.due_date'
        ));
    }

    public function byUser($userId)
    {
        return $this->withFines($this->many(
            'SELECT l.*, b.title, u.name AS member, ' . $this->paidSql() . ' FROM loans l
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
