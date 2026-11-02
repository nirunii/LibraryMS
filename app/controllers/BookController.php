<?php
// CONTROLLER: book catalogue (list/search for everyone; add/edit/delete for staff).
class BookController extends Controller
{
    private $blank = ['isbn' => '', 'title' => '', 'author' => '', 'category' => '', 'copies_total' => 1];

    public function index()
    {
        $this->requireLogin();
        $q = trim($_GET['q'] ?? '');
        $cat = $_GET['cat'] ?? '';
        $book = new Book();
        $this->view('books/index', [
            'books' => $book->all($q, $cat), 'categories' => $book->categories(), 'q' => $q, 'cat' => $cat,
        ]);
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
        $this->flash('success', 'Book updated.');
        $this->redirect('books');
    }

    public function delete($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('books'); }
        $this->checkCsrf();
        try {
            (new Book())->delete((int)$id);
            $this->flash('success', 'Book deleted.');
        } catch (PDOException $ex) {
            $this->flash('error', 'This book has loan history and cannot be deleted.');
        }
        $this->redirect('books');
    }
}
