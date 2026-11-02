<?php
// CONTROLLER: registration, login, logout.
class AuthController extends Controller
{
    public function login()
    {
        if (user()) { $this->redirect('dashboard'); }
        $errors = [];
        $old = [];
        if ($this->isPost()) {
            $this->checkCsrf();
            $old = $_POST;
            $u = (new User())->authenticate(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
            if ($u) {
                session_regenerate_id(true);
                $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
                $this->redirect('dashboard');
            }
            $errors[] = 'Incorrect email or password.';
        }
        $this->view('auth/login', ['errors' => $errors, 'old' => $old]);
    }

    public function register()
    {
        if (user()) { $this->redirect('dashboard'); }
        $errors = [];
        $old = [];
        if ($this->isPost()) {
            $this->checkCsrf();
            $old = $_POST;
            $model = new User();
            $data = ['name' => $_POST['name'] ?? '', 'email' => trim($_POST['email'] ?? ''), 'password' => $_POST['password'] ?? '', 'role' => 'member'];
            $errors = $model->validate($data);
            if (($_POST['password'] ?? '') !== ($_POST['confirm'] ?? '')) { $errors[] = 'Passwords do not match.'; }
            if (!$errors) {
                $model->create($data);
                $this->flash('success', 'Account created. Please log in.');
                $this->redirect('auth/login');
            }
        }
        $this->view('auth/register', ['errors' => $errors, 'old' => $old]);
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . url('auth/login'));
        exit;
    }
}
