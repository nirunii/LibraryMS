<?php /** Variables passed in by the Controller:
 * @var array|null $flash
 */ ?>
<?php
$seg = explode('/', trim($_GET['url'] ?? '', '/'))[0];
if ($seg === '') { $seg = user() ? 'dashboard' : 'books'; }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>LibraryMS</title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="<?= user() ? 'has-side' : 'guest-page' ?>">
<?php if (user()): ?>
<div class="shell">
  <aside class="side">
    <div class="brand"><span class="mark"><?= ui_icon('book') ?></span><span>Library<b>MS</b></span></div>
    <div class="who">
      <div class="avatar"><?= e(ui_initials(user()['name'])) ?></div>
      <strong><?= e(user()['name']) ?></strong>
      <small><?= e(ucfirst(user()['role'])) ?></small>
    </div>
    <nav class="menu">
      <a class="<?= $seg === 'dashboard' ? 'on' : '' ?>" href="<?= url('dashboard') ?>"><?= ui_icon('home') ?>Dashboard</a>
      <a class="<?= $seg === 'books' ? 'on' : '' ?>" href="<?= url('books') ?>"><?= ui_icon('book') ?>Books</a>
      <a class="<?= $seg === 'loans' ? 'on' : '' ?>" href="<?= url('loans') ?>"><?= ui_icon('loans') ?><?= is_staff() ? 'Loans' : 'My loans' ?></a>
      <?php if (is_staff()): ?>
        <a class="<?= $seg === 'fines' ? 'on' : '' ?>" href="<?= url('fines') ?>"><?= ui_icon('cash') ?>Fines</a>
        <a class="<?= $seg === 'members' ? 'on' : '' ?>" href="<?= url('members') ?>"><?= ui_icon('users') ?>Members</a>
        <a class="<?= $seg === 'reports' ? 'on' : '' ?>" href="<?= url('reports') ?>"><?= ui_icon('chart') ?>Reports</a>
      <?php endif; ?>
      <?php if (user()['role'] === 'admin'): ?>
        <a class="<?= $seg === 'audit' ? 'on' : '' ?>" href="<?= url('audit') ?>"><?= ui_icon('audit') ?>Audit log</a>
        <a class="<?= $seg === 'settings' ? 'on' : '' ?>" href="<?= url('settings') ?>"><?= ui_icon('sliders') ?>Settings</a>
      <?php endif; ?>
      <a class="<?= $seg === 'profile' ? 'on' : '' ?>" href="<?= url('profile') ?>"><?= ui_icon('user') ?>My profile</a>
    </nav>
    <a class="logout" href="<?= url('auth/logout') ?>"><?= ui_icon('logout') ?>Logout</a>
  </aside>
  <main class="content">
<?php elseif ($seg === 'books'): /* guests browsing the public catalogue */ ?>
<div class="public">
  <header class="topbar">
    <div class="brand"><span class="mark"><?= ui_icon('book') ?></span><span>Library<b>MS</b></span></div>
    <nav class="topnav"><a class="on" href="<?= url('books') ?>">Books</a></nav>
    <div class="topbar-actions">
      <a class="btn light sm" href="<?= url('auth/login') ?>">Log in</a>
      <a class="btn sm" href="<?= url('auth/register') ?>">Create account</a>
    </div>
  </header>
  <main class="content">
<?php else: ?>
<div class="guest-wrap">
  <main class="guest">
<?php endif; ?>
<?php if (!empty($flash)): ?>
  <div class="alert <?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>
