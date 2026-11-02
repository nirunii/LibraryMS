<?php /** Variables passed in by the Controller:
 * @var array $users
 * @var string $q
 */ ?>
<h1>Members</h1>
<form class="tools" method="get" action="<?= BASE_URL ?>/index.php">
  <input type="hidden" name="url" value="members">
  <input class="grow" name="q" value="<?= e($q) ?>" placeholder="Search by name or email">
  <button class="btn">Search</button>
  <a class="btn light" href="<?= url('members/create') ?>">+ Add member</a>
</form>
<div class="card wrap">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Books on loan</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td>
        <td><span class="pill <?= $u['role'] === 'member' ? 'pr' : 'ok' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
        <td><?= (int)$u['active_loans'] ?></td>
        <td class="actions">
          <?php if ($u['can_manage']): ?>
            <a class="btn light sm" href="<?= url('members/edit/' . $u['id']) ?>">Edit</a>
            <form method="post" action="<?= url('members/delete/' . $u['id']) ?>" onsubmit="return confirm('Delete this user?')">
              <?= csrf_field() ?><button class="btn danger sm">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
