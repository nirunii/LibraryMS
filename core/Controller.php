<?php
// Base class for all controllers: rendering, redirects, flash messages, access control.
class Controller
{
    protected function view($view, $data = [])
    {
        extract($data);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        require ROOT . '/app/views/layout/header.php';
        require ROOT . '/app/views/' . $view . '.php';
        require ROOT . '/app/views/layout/footer.php';
    }

    protected function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function flash($type, $msg)
    {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    }

    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function checkCsrf()
    {
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(400);
            exit('Invalid form token. Go back and try again.');
        }
    }

    protected function requireLogin()
    {
        if (!user()) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $next = trim($_GET['url'] ?? '', '/');
                if (preg_match('~^[a-z]+(/[A-Za-z0-9]+)*$~', $next)) { $_SESSION['return_to'] = $next; }
            }
            $this->flash('info', 'Please log in to continue.');
            $this->redirect('auth/login');
        }
    }

    protected function requireRole($roles)
    {
        $this->requireLogin();
        if (!in_array(user()['role'], $roles, true)) {
            http_response_code(403);
            $this->view('errors/403');
            exit;
        }
    }

    public function notFound()
    {
        http_response_code(404);
        $this->view('errors/404');
    }
}
