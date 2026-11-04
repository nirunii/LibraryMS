<?php /** Variables passed in by the Controller:
 * @var array $u
 * @var array $errors
 * @var array $pwErrors
 * @var float $outstanding
 * @var int $activeLoans
 */ ?>
<h1>My profile</h1>
<div class="card kv">
  <div><small>Membership no.</small><b><?= e(member_no($u['id'])) ?></b></div>
  <div><small>Role</small><b><?= e(ucfirst($u['role'])) ?></b></div>
  <div><small>Status</small><b><?= $u['status'] === 'suspended' ? '<span class="pill dg">Suspended</span>' : '<span class="pill ok">Active</span>' ?></b></div>
  <div><small>Member since</small><b><?= e(substr($u['created_at'], 0, 10)) ?></b></div>
  <div><small>Books on loan</small><b><?= (int)$activeLoans ?></b></div>
  <div><small>Unpaid fines</small><b><?= money($outstanding) ?></b></div>
</div>

<div class="two">
  <div class="card">
    <h2>My details</h2>
    <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" action="<?= url('profile/update') ?>">
      <?= csrf_field() ?>
      <label>Full name <input name="name" value="<?= e($u['name']) ?>" required></label>
      <label>Email <input type="email" name="email" value="<?= e($u['email']) ?>" required></label>
      <label>Phone <input name="phone" value="<?= e($u['phone'] ?? '') ?>"></label>
      <button class="btn">Save details</button>
    </form>
  </div>
  <div class="card">
    <h2>Change password</h2>
    <?php foreach ($pwErrors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post" action="<?= url('profile/password') ?>">
      <?= csrf_field() ?>
      <label>Current password <input type="password" name="current" required></label>
      <label>New password (min 6 characters) <input type="password" name="new" required></label>
      <label>Confirm new password <input type="password" name="confirm" required></label>
      <button class="btn">Change password</button>
    </form>
  </div>
</div>
