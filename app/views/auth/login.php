<?php /** Variables passed in by the Controller:
 * @var array $errors
 * @var array $old
 */ ?>
<div class="card narrow">
  <div class="brand center"><span class="mark"><?= ui_icon('book') ?></span><b>Library<i>MS</i></b></div>
  <h1>Welcome back</h1>
  <p class="muted">Log in to borrow, reserve and manage books.</p>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="<?= url('auth/login') ?>">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn block">Log in</button>
  </form>
  <p class="muted center">No account? <a href="<?= url('auth/register') ?>">Register as a member</a></p>
  <p class="muted center"><a href="<?= url('books') ?>">Browse the catalogue without logging in</a></p>
</div>
