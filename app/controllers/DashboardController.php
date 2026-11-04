<?php
// CONTROLLER: dashboard with statistics and chart data.
class DashboardController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        (new Reservation())->refresh();
        $loan = new Loan();
        $pay = new Payment();
        $data = ['monthly' => $loan->monthly(is_staff() ? null : user()['id']), 'top' => (new Book())->topBorrowed(5),
                 'stats' => [], 'myActive' => [], 'myFines' => 0.0, 'myReady' => 0];
        if (is_staff()) {
            $data['stats'] = [
                'Total book copies' => (new Book())->countAll(),
                'Members'           => (new User())->countMembers(),
                'Books on loan'     => $loan->countActive(),
                'Overdue'           => $loan->countOverdue(),
                'Unpaid fines'      => money($pay->outstandingTotal()),
            ];
        } else {
            $uid = (int)user()['id'];
            $data['myActive'] = $loan->activeByUser($uid);
            $data['myFines'] = $pay->outstandingForUser($uid);
            $data['myReady'] = count((new Reservation())->readyBookIds($uid));
        }
        $this->view('dashboard/index', $data);
    }
}
