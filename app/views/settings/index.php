<?php /** Variables passed in by the Controller:
 * @var array $defs
 * @var array $values
 * @var array $errors
 */ ?>
<h1>Settings</h1>
<p class="muted">These rules apply to the whole library. Changes take effect immediately.</p>
<div class="card narrow wide">
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="<?= url('settings/update') ?>">
    <?= csrf_field() ?>
    <?php foreach ($defs as $key => $d): ?>
      <label><?= e($d[0]) ?>
        <input type="number" name="<?= e($key) ?>" min="<?= (int)$d[2] ?>" max="<?= (int)$d[3] ?>" value="<?= e($values[$key] ?? $d[1]) ?>" required>
        <small class="muted"><?= e($d[4]) ?></small>
      </label>
    <?php endforeach; ?>
    <button class="btn">Save settings</button>
  </form>
</div>
