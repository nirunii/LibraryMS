<?php /** Variables passed in by the Controller:
 * @var array $loans
 * @var array $members
 * @var array $books
 * @var array $reservations
 */ ?>
<?php if (is_staff()): ?>
  <h1>Loans</h1>
  <div class="card">
    <h2>Issue a book</h2>
    <form method="post" action="<?= url('loans/borrow') ?>" class="tools">
      <?= csrf_field() ?>
      <select name="user_id" required>
        <option value="">Select member</option>
        <?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['email']) ?>)</option><?php endforeach; ?>
      </select>
      <select name="book_id" required>
        <option value="">Select book</option>
        <?php foreach ($books as $b): ?>
          <?php if ($b['copies_available'] > 0): ?><option value="<?= (int)$b['id'] ?>"><?= e($b['title']) ?></option><?php endif; ?>
        <?php endforeach; ?>
      </select>
      <button class="btn">Issue book</button>
    </form>
  </div>

  <h2>Books currently on loan</h2>
  <div class="card wrap">
    <table>
      <thead><tr><th>Book</th><th>Member</th><th>Borrowed</th><th>Due date</th><th>Status</th><th>Fine</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($loans as $l): ?>
        <tr>
          <td><?= e($l['title']) ?></td><td><?= e($l['member']) ?></td>
          <td><?= e(substr($l['borrowed_at'], 0, 10)) ?></td><td><?= e($l['due_date']) ?></td>
          <td><span class="pill <?= $l['overdue'] ? 'dg' : 'ok' ?>"><?= $l['overdue'] ? 'Overdue' : 'On loan' ?></span></td>
          <td><?= money($l['fine']) ?></td>
          <td class="actions">
            <form method="post" action="<?= url('loans/returnBook/' . $l['id']) ?>">
              <?= csrf_field() ?><button class="btn sm">Return</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$loans): ?><tr><td colspan="7" class="muted">No books are on loan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <h1>My loans</h1>
  <div class="card wrap">
    <table>
      <thead><tr><th>Book</th><th>Borrowed</th><th>Due date</th><th>Returned</th><th>Fine</th></tr></thead>
      <tbody>
      <?php foreach ($loans as $l): ?>
        <tr>
          <td><?= e($l['title']) ?></td><td><?= e(substr($l['borrowed_at'], 0, 10)) ?></td><td><?= e($l['due_date']) ?></td>
          <td><?= $l['returned_at'] ? e(substr($l['returned_at'], 0, 10)) : '<span class="pill ' . ($l['overdue'] ? 'dg">Overdue' : 'ok">On loan') . '</span>' ?></td>
          <td><?= money($l['fine']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$loans): ?><tr><td colspan="5" class="muted">You have not borrowed any books yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <h2>My reservations</h2>
  <div class="card wrap">
    <table>
      <thead><tr><th>Book</th><th>Reserved on</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($reservations as $r): ?>
        <tr>
          <td><?= e($r['title']) ?></td><td><?= e(substr($r['reserved_at'], 0, 10)) ?></td>
          <td><?= $r['copies_available'] > 0 ? '<span class="pill ok">Ready to borrow</span>' : '<span class="pill pr">Waiting</span>' ?></td>
          <td class="actions">
            <form method="post" action="<?= url('loans/cancelReservation/' . $r['id']) ?>">
              <?= csrf_field() ?><button class="btn danger sm">Cancel</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$reservations): ?><tr><td colspan="4" class="muted">No active reservations.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
