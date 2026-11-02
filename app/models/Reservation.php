<?php
// MODEL: reservations (queue for books with no free copy).
class Reservation extends Model
{
    // Returns [success, message]
    public function reserve($userId, $bookId)
    {
        $book = $this->one('SELECT * FROM books WHERE id = ?', [$bookId]);
        if (!$book) { return [false, 'Book not found.']; }
        if ($book['copies_available'] > 0) { return [false, 'A copy is available. Borrow it instead.']; }
        if ($this->scalar("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND book_id = ? AND status = 'active'", [$userId, $bookId]) > 0) {
            return [false, 'You already reserved this book.'];
        }
        if ($this->scalar('SELECT COUNT(*) FROM loans WHERE user_id = ? AND book_id = ? AND returned_at IS NULL', [$userId, $bookId]) > 0) {
            return [false, 'You already have this book on loan.'];
        }
        $this->db->prepare('INSERT INTO reservations (book_id, user_id) VALUES (?, ?)')->execute([$bookId, $userId]);
        return [true, 'Book reserved. You will be first in line when a copy is returned.'];
    }

    public function cancel($id, $userId)
    {
        $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'active'")->execute([$id, $userId]);
    }

    public function byUser($userId)
    {
        return $this->many(
            "SELECT r.*, b.title, b.copies_available FROM reservations r JOIN books b ON b.id = r.book_id
             WHERE r.user_id = ? AND r.status = 'active' ORDER BY r.reserved_at",
            [$userId]
        );
    }
}
