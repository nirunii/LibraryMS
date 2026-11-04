<?php
// CONTROLLER: book catalogue (list/search for everyone; add/edit/delete for staff).
class BookController extends Controller
{
    private $blank = ['isbn' => '', 'title' => '', 'author' => '', 'category' => '', 'copies_total' => 1];

    // The catalogue is public: guests can browse and search without logging in.
    public function index()
    {
        $q = trim($_GET['q'] ?? '');
        $cat = $_GET['cat'] ?? '';
        (new Reservation())->refresh();
        $book = new Book();
        $this->view('books/index', [
            'books' => $book->all($q, $cat), 'categories' => $book->categories(), 'q' => $q, 'cat' => $cat,
            'myReady' => (user() && !is_staff()) ? (new Reservation())->readyBookIds((int)user()['id']) : [],
        ]);
    }

    // Public details page for one book.
    public function show($id = 0)
    {
        (new Reservation())->refresh();
        $book = (new Book())->detail((int)$id);
        if (!$book) { $this->notFound(); return; }
        $mine = (user() && !is_staff()) ? in_array((int)$book['id'], (new Reservation())->readyBookIds((int)user()['id']), true) : false;
        $this->view('books/show', ['book' => $book, 'mine' => $mine]);
    }

    public function create()
    {
        $this->requireRole(['admin', 'librarian']);
        $this->view('books/form', ['book' => $this->blank, 'errors' => [], 'action' => 'books/store', 'title' => 'Add book']);
    }

    public function store()
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        $model = new Book();
        $errors = $model->validate($_POST);
        if ($errors) {
            $this->view('books/form', ['book' => $_POST, 'errors' => $errors, 'action' => 'books/store', 'title' => 'Add book']);
            return;
        }
        $model->create($_POST);
        Audit::log('book.add', trim($_POST['title']));
        $this->flash('success', 'Book added.');
        $this->redirect('books');
    }

    public function edit($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        $book = (new Book())->find((int)$id);
        if (!$book) { $this->notFound(); return; }
        $this->view('books/form', ['book' => $book, 'errors' => [], 'action' => 'books/update/' . $book['id'], 'title' => 'Edit book']);
    }

    public function update($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        $model = new Book();
        if (!$model->find((int)$id)) { $this->notFound(); return; }
        $errors = $model->validate($_POST, (int)$id);
        if ($errors) {
            $this->view('books/form', ['book' => $_POST, 'errors' => $errors, 'action' => 'books/update/' . (int)$id, 'title' => 'Edit book']);
            return;
        }
        $model->update((int)$id, $_POST);
        Audit::log('book.edit', trim($_POST['title']));
        $this->flash('success', 'Book updated.');
        $this->redirect('books');
    }

    public function delete($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        $model = new Book();
        $b = $model->find((int)$id);
        try {
            $model->delete((int)$id);
            Audit::log('book.delete', $b ? $b['title'] : 'Book #' . (int)$id);
            $this->flash('success', 'Book deleted.');
        } catch (PDOException $ex) {
            $this->flash('error', 'This book has loan history and cannot be deleted.');
        }
        $this->redirect('books');
    }
}
