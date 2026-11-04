<?php /** Variables passed in by the Controller:
 * @var array $errors
 * @var array $old
 */ ?>
<div class="card narrow">
  <div class="brand center"><span class="mark"><?= ui_icon('book') ?></span><b>Library<i>MS</i></b></div>
  <h1>Create your account</h1>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="<?= url('auth/register') ?>">
    <?= csrf_field() ?>
    <label>Full name <input name="name" value="<?= e($old['name'] ?? '') ?>" required></label>
    <label>Email <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required></label>
    <label>Password (min 6 characters) <input type="password" name="password" required></label>
    <label>Confirm password <input type="password" name="confirm" required></label>
    <button class="btn block">Create account</button>
  </form>
  <p class="muted center">Already registered? <a href="<?= url('auth/login') ?>">Log in</a></p>
  <p class="muted center"><a href="<?= url('books') ?>">Browse the catalogue without logging in</a></p>
</div>
