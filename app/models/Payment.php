<?php
// MODEL: fine payments. A fine becomes payable when the book is returned; staff record payments
// (cash, card, online) or an admin waives the fine. Balance = fine - payments.
class Payment extends Model
{
    const METHODS = ['cash', 'card', 'online', 'waived'];

    // SQL piece: total paid per loan
    private function paidJoin()
    {
        return 'LEFT JOIN (SELECT loan_id, SUM(amount) AS paid FROM payments GROUP BY loan_id) p ON p.loan_id = l.id';
    }

    public function outstandingForUser($userId)
    {
        return (float)$this->scalar(
            'SELECT COALESCE(SUM(GREATEST(l.fine_amount - COALESCE(p.paid, 0), 0)), 0) FROM loans l ' . $this->paidJoin() .
            ' WHERE l.returned_at IS NOT NULL AND l.user_id = ?',
            [$userId]
        );
    }

    public function outstandingTotal()
    {
        return (float)$this->scalar(
            'SELECT COALESCE(SUM(GREATEST(l.fine_amount - COALESCE(p.paid, 0), 0)), 0) FROM loans l ' . $this->paidJoin() .
            ' WHERE l.returned_at IS NOT NULL'
        );
    }

    // Returned loans that still have an unpaid balance.
    public function unpaidLoans()
    {
        return $this->many(
            'SELECT l.id, l.user_id, l.fine_amount, l.returned_at, l.due_date, b.title, u.name AS member,
                    COALESCE(p.paid, 0) AS paid, (l.fine_amount - COALESCE(p.paid, 0)) AS balance
             FROM loans l JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id ' . $this->paidJoin() .
            ' WHERE l.returned_at IS NOT NULL AND l.fine_amount > COALESCE(p.paid, 0)
             ORDER BY l.returned_at DESC'
        );
    }

    public function balanceForLoan($loanId)
    {
        $row = $this->one(
            'SELECT l.fine_amount, l.returned_at, COALESCE(p.paid, 0) AS paid FROM loans l ' . $this->paidJoin() . ' WHERE l.id = ?',
            [$loanId]
        );
        if (!$row || !$row['returned_at']) { return null; }
        return round((float)$row['fine_amount'] - (float)$row['paid'], 2);
    }

    // Returns [success, message, paymentId]
    public function record($loanId, $amount, $method, $note, $staffId, $isAdmin)
    {
        $loan = $this->one('SELECT id, user_id FROM loans WHERE id = ?', [$loanId]);
        if (!$loan) { return [false, 'Loan not found.', null]; }
        if (!in_array($method, self::METHODS, true)) { return [false, 'Choose a payment method.', null]; }
        if ($method === 'waived' && !$isAdmin) { return [false, 'Only an admin can waive a fine.', null]; }
        $balance = $this->balanceForLoan($loanId);
        if ($balance === null) { return [false, 'The book must be returned before its fine can be paid.', null]; }
        if ($balance <= 0) { return [false, 'This fine is already settled.', null]; }
        $amount = round((float)$amount, 2);
        if ($amount <= 0) { return [false, 'Enter an amount greater than zero.', null]; }
        if ($amount > $balance) { return [false, 'The amount is more than the balance of ' . money($balance) . '.', null]; }
        $st = $this->db->prepare('INSERT INTO payments (loan_id, user_id, amount, method, note, received_by) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([$loanId, $loan['user_id'], $amount, $method, substr(trim((string)$note), 0, 200), $staffId]);
        return [true, 'Payment recorded.', (int)$this->db->lastInsertId()];
    }

    // One payment with everything needed for a receipt.
    public function find($id)
    {
        return $this->one(
            'SELECT p.*, b.title, l.fine_amount, l.due_date, l.returned_at, m.name AS member, m.email AS member_email,
                    s.name AS staff_name
             FROM payments p JOIN loans l ON l.id = p.loan_id JOIN books b ON b.id = l.book_id
             JOIN users m ON m.id = p.user_id LEFT JOIN users s ON s.id = p.received_by
             WHERE p.id = ?',
            [$id]
        );
    }

    public function byUser($userId)
    {
        return $this->many(
            'SELECT p.*, b.title FROM payments p JOIN loans l ON l.id = p.loan_id JOIN books b ON b.id = l.book_id
             WHERE p.user_id = ? ORDER BY p.paid_at DESC, p.id DESC',
            [$userId]
        );
    }

    public function recent($limit = 15)
    {
        $limit = (int)$limit;
        return $this->many(
            "SELECT p.*, b.title, m.name AS member FROM payments p JOIN loans l ON l.id = p.loan_id
             JOIN books b ON b.id = l.book_id JOIN users m ON m.id = p.user_id ORDER BY p.paid_at DESC, p.id DESC LIMIT $limit"
        );
    }

    // Money actually received (waived fines are not money) and the total waived.
    public function totals()
    {
        $row = $this->one(
            "SELECT COALESCE(SUM(CASE WHEN method <> 'waived' THEN amount ELSE 0 END), 0) AS collected,
                    COALESCE(SUM(CASE WHEN method = 'waived' THEN amount ELSE 0 END), 0) AS waived
             FROM payments"
        );
        return ['collected' => (float)$row['collected'], 'waived' => (float)$row['waived'], 'outstanding' => $this->outstandingTotal()];
    }
}
