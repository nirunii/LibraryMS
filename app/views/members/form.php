<?php /** Variables passed in by the Controller:
 * @var array $u
 * @var array $errors
 * @var string $action
 * @var string $title
 * @var bool $isNew
 * @var bool $canAssignRole
 */ ?>
<div class="card narrow">
  <h1><?= e($title) ?></h1>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="<?= url($action) ?>">
    <?= csrf_field() ?>
    <label>Full name <input name="name" value="<?= e($u['name']) ?>" required></label>
    <label>Email <input type="email" name="email" value="<?= e($u['email']) ?>" required></label>
    <label>Phone (optional) <input name="phone" value="<?= e($u['phone'] ?? '') ?>"></label>
    <label><?= $isNew ? 'Password' : 'New password (leave blank to keep current)' ?>
      <input type="password" name="password" <?= $isNew ? 'required' : '' ?>></label>
    <?php if ($canAssignRole): ?>
      <label>Role
        <select name="role">
          <?php foreach (['member', 'librarian', 'admin'] as $r): ?>
            <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <button class="btn">Save</button>
    <a class="btn light" href="<?= url('members') ?>">Cancel</a>
  </form>
</div>
