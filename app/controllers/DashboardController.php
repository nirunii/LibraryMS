<?php
// CONTROLLER: dashboard with statistics and chart data.
class DashboardController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $loan = new Loan();
        $data = ['monthly' => $loan->monthly(is_staff() ? null : user()['id']), 'top' => (new Book())->topBorrowed(5)];
        if (is_staff()) {
            $data['stats'] = [
                'Total book copies' => (new Book())->countAll(),
                'Members'           => (new User())->countMembers(),
                'Books on loan'     => $loan->countActive(),
                'Overdue'           => $loan->countOverdue(),
            ];
        } else {
            $data['myActive'] = $loan->activeByUser(user()['id']);
        }
        $this->view('dashboard/index', $data);
    }
}
