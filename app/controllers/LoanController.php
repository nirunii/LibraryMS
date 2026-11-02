<?php
// CONTROLLER: borrowing, returning and reserving books.
class LoanController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $loan = new Loan();
        if (is_staff()) {
            $this->view('loans/index', [
                'loans' => $loan->active(), 'members' => (new User())->membersOnly(),
                'books' => (new Book())->all(),
            ]);
        } else {
            $this->view('loans/index', [
                'loans' => $loan->byUser(user()['id']), 'reservations' => (new Reservation())->byUser(user()['id']),
            ]);
        }
    }

    public function borrow()
    {
        $this->requireLogin();
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        $bookId = (int)($_POST['book_id'] ?? 0);
        $memberId = (is_staff() && !empty($_POST['user_id'])) ? (int)$_POST['user_id'] : (int)user()['id'];
        list($ok, $msg) = (new Loan())->borrow($memberId, $bookId);
        $this->flash($ok ? 'success' : 'error', $msg);
        $this->redirect(is_staff() ? 'loans' : 'books');
    }

    public function returnBook($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('loans'); }
        $this->checkCsrf();
        list($ok, $msg) = (new Loan())->returnBook((int)$id);
        $this->flash($ok ? 'success' : 'error', $msg);
        $this->redirect('loans');
    }

    public function reserve()
    {
        $this->requireRole(['member']);
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        list($ok, $msg) = (new Reservation())->reserve((int)user()['id'], (int)($_POST['book_id'] ?? 0));
        $this->flash($ok ? 'success' : 'error', $msg);
        $this->redirect('books');
    }

    public function cancelReservation($id = 0)
    {
        $this->requireRole(['member']);
        if (!$this->isPost()) { $this->redirect('loans'); }
        $this->checkCsrf();
        (new Reservation())->cancel((int)$id, (int)user()['id']);
        $this->flash('success', 'Reservation cancelled.');
        $this->redirect('loans');
    }
}
