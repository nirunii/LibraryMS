<?php
// CONTROLLER: fines owed, recording payments (or waiving) and printable receipts.
class FineController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin', 'librarian']);
        $pay = new Payment();
        $this->view('fines/index', [
            'unpaid' => $pay->unpaidLoans(), 'recent' => $pay->recent(15), 'totals' => $pay->totals(),
            'isAdmin' => user()['role'] === 'admin',
        ]);
    }

    public function pay($loanId = 0)
    {
        $this->requireRole(['admin', 'librarian']);
        if (!$this->isPost()) { $this->redirect('fines'); }
        $this->checkCsrf();
        $method = $_POST['method'] ?? '';
        $amount = str_replace(',', '', trim($_POST['amount'] ?? ''));
        list($ok, $msg, $paymentId) = (new Payment())->record(
            (int)$loanId, $amount, $method, $_POST['note'] ?? '', (int)user()['id'], user()['role'] === 'admin'
        );
        if (!$ok) {
            $this->flash('error', $msg);
            $this->redirect('fines');
        }
        Audit::log($method === 'waived' ? 'fine.waive' : 'fine.payment', 'Loan #' . (int)$loanId . ', ' . money($amount) . ' (' . $method . ')');
        $this->flash('success', 'Payment recorded. Receipt #' . $paymentId . ' is ready to print.');
        $this->redirect('fines/receipt/' . $paymentId);
    }

    // Staff can open any receipt; a member can open only their own.
    public function receipt($id = 0)
    {
        $this->requireLogin();
        $p = (new Payment())->find((int)$id);
        if (!$p || (!is_staff() && (int)$p['user_id'] !== (int)user()['id'])) { $this->notFound(); return; }
        $this->view('fines/receipt', ['p' => $p, 'balance' => (new Payment())->balanceForLoan((int)$p['loan_id'])]);
    }
}
