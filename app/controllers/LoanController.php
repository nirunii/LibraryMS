<?php
// CONTROLLER: borrowing, renewing, returning and reserving books, and the holds queue.
class LoanController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        (new Reservation())->refresh();
        $loan = new Loan();
        if (is_staff()) {
            $this->view('loans/index', [
                'loans' => $loan->active(), 'members' => (new User())->membersOnly(), 'books' => (new Book())->all(),
                'queue' => (new Reservation())->queue(), 'reservations' => [], 'payments' => [], 'outstanding' => 0.0,
            ]);
        } else {
            $uid = (int)user()['id'];
            $pay = new Payment();
            $this->view('loans/index', [
                'loans' => $loan->byUser($uid), 'reservations' => (new Reservation())->byUser($uid),
                'payments' => $pay->byUser($uid), 'outstanding' => $pay->outstandingForUser($uid),
                'members' => [], 'books' => [], 'queue' => [],
            ]);
        }
    }

    public function borrow()
    {
        $this->requireLogin();
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        (new Reservation())->refresh();
        $bookId = (int)($_POST['book_id'] ?? 0);
        $memberId = (is_staff() && !empty($_POST['user_id'])) ? (int)$_POST['user_id'] : (int)user()['id'];
        list($ok, $msg) = (new Loan())->borrow($memberId, $bookId);
        if ($ok) { Audit::log('loan.issue', 'Book #' . $bookId . ' to member #' . $memberId); }
        $this->flash($ok ? 'success' : 'error', $msg);
        $this->redirect(is_staff() ? 'loans' : 'books');
    }

    public function renew($id = 0)
    {
        $this->requireLogin();
        if (!$this->isPost()) { $this->redirect('loans'); }
        $this->checkCsrf();
        list($ok, $msg) = (new Loan())->renew((int)$id, (int)user()['id'], is_staff());
        if ($ok) { Audit::log('loan.renew', 'Loan #' . (int)$id); }
        $this->flash($ok ? 'success' : 'error', $msg);
        $this->redirect('loans');
    }

    public function returnBook($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('loans'); }
        $this->checkCsrf();
        list($ok, $msg) = (new Loan())->returnBook((int)$id);
        if ($ok) { Audit::log('loan.return', 'Loan #' . (int)$id . ' - ' . $msg); }
        $this->flash($ok ? 'success' : 'error', $msg);
        $this->redirect('loans');
    }

    public function reserve()
    {
        $this->requireRole(['member']);
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        $bookId = (int)($_POST['book_id'] ?? 0);
        list($ok, $msg) = (new Reservation())->reserve((int)user()['id'], $bookId);
        if ($ok) { Audit::log('hold.add', 'Book #' . $bookId); }
        $this->flash($ok ? 'success' : 'error', $msg);
        $this->redirect('books');
    }

    // A member cancels one of their own holds.
    public function cancelReservation($id = 0)
    {
        $this->requireRole(['member']);
        if (!$this->isPost()) { $this->redirect('loans'); }
        $this->checkCsrf();
        $ok = (new Reservation())->cancel((int)$id, (int)user()['id']);
        if ($ok) { Audit::log('hold.cancel', 'Hold #' . (int)$id . ' (by member)'); }
        $this->flash($ok ? 'success' : 'error', $ok ? 'Reservation cancelled.' : 'Reservation not found.');
        $this->redirect('loans');
    }

    // Staff cancel any hold (for example when a member did not collect the book).
    public function cancelHold($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('loans'); }
        $this->checkCsrf();
        $ok = (new Reservation())->cancel((int)$id);
        if ($ok) { Audit::log('hold.cancel', 'Hold #' . (int)$id . ' (by staff)'); }
        $this->flash($ok ? 'success' : 'error', $ok ? 'Hold cancelled.' : 'Hold not found.');
        $this->redirect('loans');
    }
}
