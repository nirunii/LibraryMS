<?php
// CONTROLLER: "My profile" for every logged-in user: contact details and password.
class ProfileController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->show([], []);
    }

    public function update()
    {
        $this->requireLogin();
        if (!$this->isPost()) { $this->redirect('profile'); }
        $this->checkCsrf();
        $model = new User();
        $id = (int)user()['id'];
        $data = ['name' => $_POST['name'] ?? '', 'email' => trim($_POST['email'] ?? ''), 'phone' => $_POST['phone'] ?? '', 'role' => user()['role']];
        $errors = $model->validate($data, $id);
        if ($errors) { $this->show($errors, [], $data); return; }
        $model->updateProfile($id, $data);
        $_SESSION['user']['name'] = trim($data['name']);
        $_SESSION['user']['email'] = $data['email'];
        Audit::log('profile.update', 'Details updated');
        $this->flash('success', 'Your details were saved.');
        $this->redirect('profile');
    }

    public function password()
    {
        $this->requireLogin();
        if (!$this->isPost()) { $this->redirect('profile'); }
        $this->checkCsrf();
        $new = $_POST['new'] ?? '';
        if ($new !== ($_POST['confirm'] ?? '')) { $this->show([], ['The new passwords do not match.']); return; }
        list($ok, $msg) = (new User())->changePassword((int)user()['id'], $_POST['current'] ?? '', $new);
        if (!$ok) { $this->show([], [$msg]); return; }
        Audit::log('profile.password', 'Password changed');
        $this->flash('success', $msg);
        $this->redirect('profile');
    }

    private function show($errors, $pwErrors, $override = [])
    {
        $id = (int)user()['id'];
        $u = (new User())->find($id);
        if ($override) { $u = array_merge($u, $override); }
        $this->view('profile/index', [
            'u' => $u, 'errors' => $errors, 'pwErrors' => $pwErrors,
            'outstanding' => (new Payment())->outstandingForUser($id), 'activeLoans' => (new Loan())->countActive($id),
        ]);
    }
}
