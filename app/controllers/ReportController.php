<?php
// CONTROLLER: reports for staff.
class ReportController extends Controller
{
    public function index()
    {
        $this->requireRole(['admin', 'librarian']);
        $loan = new Loan();
        $this->view('reports/index', [
            'overdue' => $loan->overdue(), 'top' => (new Book())->topBorrowed(10),
            'totals' => (new Payment())->totals(), 'rate' => Setting::get('fine_per_day'),
        ]);
    }
}
