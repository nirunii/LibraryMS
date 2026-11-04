<?php /** Variables passed in by the Controller:
 * @var array $logs
 * @var array $actions
 * @var string $action
 */ ?>
<h1>Audit log</h1>
<p class="muted">A record of who did what. Showing the latest <?= count($logs) ?> entries.</p>
<form class="tools" method="get" action="<?= BASE_URL ?>/index.php">
  <input type="hidden" name="url" value="audit">
  <select name="action">
    <option value="">All actions</option>
    <?php foreach ($actions as $a): ?><option <?= $a === $action ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
  </select>
  <button class="btn">Filter</button>
</form>
<div class="card wrap">
  <table>
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr><td><?= e($l['created_at']) ?></td><td><?= e($l['user_name']) ?></td>
        <td><span class="pill pr"><?= e($l['action']) ?></span></td><td><?= e($l['details']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="4" class="muted">Nothing recorded yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
