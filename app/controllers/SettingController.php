<?php
// CONTROLLER: library settings (admin only). Changing them changes the rules everywhere at once.
class SettingController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin']);
        $this->view('settings/index', ['defs' => Setting::definitions(), 'values' => (new Setting())->all(), 'errors' => []]);
    }

    public function update()
    {
        $this->requireRole(['admin']);
        if (!$this->isPost()) { $this->redirect('settings'); }
        $this->checkCsrf();
        $model = new Setting();
        $errors = $model->validate($_POST);
        if ($errors) {
            $this->view('settings/index', ['defs' => Setting::definitions(), 'values' => $_POST, 'errors' => $errors]);
            return;
        }
        $summary = $model->save($_POST);
        Audit::log('settings.update', $summary);
        $this->flash('success', 'Settings saved.');
        $this->redirect('settings');
    }
}
