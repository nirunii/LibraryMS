<?php
// FRONT CONTROLLER: every request enters here and is routed to a Controller.
define('ROOT', __DIR__);
require ROOT . '/config/config.php';
require ROOT . '/core/helpers.php';

spl_autoload_register(function ($class) {
    foreach (['core', 'app/models', 'app/controllers'] as $dir) {
        $file = ROOT . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});

session_start();

$routes = [
    'auth'      => 'AuthController',
    'dashboard' => 'DashboardController',
    'books'     => 'BookController',
    'members'   => 'MemberController',
    'loans'     => 'LoanController',
    'reports'   => 'ReportController',
];

$parts  = explode('/', trim($_GET['url'] ?? '', '/'));
$key    = $parts[0] !== '' ? $parts[0] : (isset($_SESSION['user']) ? 'dashboard' : 'auth');
$action = $parts[1] ?? ($key === 'auth' ? 'login' : 'index');
$params = array_slice($parts, 2);

if (!isset($routes[$key])) {
    (new Controller())->notFound();
    exit;
}

$class = $routes[$key];
$controller = new $class();
if (!is_callable([$controller, $action])) {
    (new Controller())->notFound();
    exit;
}
call_user_func_array([$controller, $action], $params);
