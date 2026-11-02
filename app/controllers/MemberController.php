<?php
// CONTROLLER: manage members and staff accounts. Only admins may assign roles.
class MemberController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin', 'librarian']);
        $q = trim($_GET['q'] ?? '');
        $users = (new User())->all($q);
        foreach ($users as &$row) { $row['can_manage'] = $this->canManage($row); }
        unset($row);
        $this->view('members/index', ['users' => $users, 'q' => $q]);
    }

    public function create()
    {
        $this->requireRole(['admin', 'librarian']);
        $this->view('members/form', ['u' => ['name' => '', 'email' => '', 'role' => 'member'], 'errors' => [], 'action' => 'members/store', 'title' => 'Add member', 'isNew' => true, 'canAssignRole' => $this->canAssignRole()]);
    }

    public function store()
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('members'); }
        $this->checkCsrf();
        $model = new User();
        $data = $_POST;
        if (user()['role'] !== 'admin') { $data['role'] = 'member'; }
        $errors = $model->validate($data);
        if ($errors) {
            $this->view('members/form', ['u' => $data, 'errors' => $errors, 'action' => 'members/store', 'title' => 'Add member', 'isNew' => true, 'canAssignRole' => $this->canAssignRole()]);
            return;
        }
        $model->create($data);
        $this->flash('success', 'Member added.');
        $this->redirect('members');
    }

    public function edit($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        $u = (new User())->find((int)$id);
        if (!$u || !$this->canManage($u)) { $this->notFound(); return; }
        $this->view('members/form', ['u' => $u, 'errors' => [], 'action' => 'members/update/' . $u['id'], 'title' => 'Edit member', 'isNew' => false, 'canAssignRole' => $this->canAssignRole()]);
    }

    public function update($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('members'); }
        $this->checkCsrf();
        $model = new User();
        $u = $model->find((int)$id);
        if (!$u || !$this->canManage($u)) { $this->notFound(); return; }
        $data = $_POST;
        if (user()['role'] !== 'admin') { $data['role'] = $u['role']; }
        $errors = $model->validate($data, (int)$id);
        if ($errors) {
            $this->view('members/form', ['u' => $data, 'errors' => $errors, 'action' => 'members/update/' . (int)$id, 'title' => 'Edit member', 'isNew' => false, 'canAssignRole' => $this->canAssignRole()]);
            return;
        }
        $model->update((int)$id, $data);
        $this->flash('success', 'Member updated.');
        $this->redirect('members');
    }

    public function delete($id = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('members'); }
        $this->checkCsrf();
        $model = new User();
        $u = $model->find((int)$id);
        if (!$u || !$this->canManage($u)) { $this->notFound(); return; }
        if ((int)$id === (int)user()['id']) {
            $this->flash('error', 'You cannot delete your own account.');
            $this->redirect('members');
        }
        try {
            $model->delete((int)$id);
            $this->flash('success', 'Member deleted.');
        } catch (PDOException $ex) {
            $this->flash('error', 'This user has loan history and cannot be deleted.');
        }
        $this->redirect('members');
    }

    // Only admins may choose a role for an account.
    private function canAssignRole()
    {
        return user()['role'] === 'admin';
    }

    // Librarians may only manage plain members; admins may manage everyone.
    private function canManage($target)
    {
        return user()['role'] === 'admin' || $target['role'] === 'member';
    }
}
