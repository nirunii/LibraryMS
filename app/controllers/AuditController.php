<?php
// CONTROLLER: audit log viewer (admin only).
class AuditController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin']);
        $model = new Audit();
        $action = trim($_GET['action'] ?? '');
        $this->view('audit/index', ['logs' => $model->recent($action, 200), 'actions' => $model->actions(), 'action' => $action]);
    }
}
