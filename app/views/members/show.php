<?php /** Variables passed in by the Controller:
 * @var array $u
 * @var array $loans
 * @var array $reservations
 * @var array $payments
 * @var float $outstanding
 * @var bool $isSelf
 */ ?>
<?php $onLoan = count(array_filter($loans, function ($l) { return !$l['returned_at']; })); ?>
<div class="page-head">
  <h1><?= e($u['name']) ?></h1>
  <div class="actions">
    <a class="btn light" href="<?= url('members/edit/' . $u['id']) ?>">Edit details</a>
    <?php if (!$isSelf): ?>
      <form method="post" action="<?= url('members/setStatus/' . $u['id']) ?>">
        <?= csrf_field() ?>
        <?php if ($u['status'] === 'suspended'): ?>
          <input type="hidden" name="status" value="active"><button class="btn">Reactivate membership</button>
        <?php else: ?>
          <input type="hidden" name="status" value="suspended"><button class="btn danger" onclick="return confirm('Suspend this membership? They will not be able to borrow.')">Suspend membership</button>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="card kv">
  <div><small>Membership no.</small><b><?= e(member_no($u['id'])) ?></b></div>
  <div><small>Email</small><b><?= e($u['email']) ?></b></div>
  <div><small>Phone</small><b><?= e($u['phone'] ?: '-') ?></b></div>
  <div><small>Role</small><b><?= e(ucfirst($u['role'])) ?></b></div>
  <div><small>Status</small><b><?= $u['status'] === 'suspended' ? '<span class="pill dg">Suspended</span>' : '<span class="pill ok">Active</span>' ?></b></div>
  <div><small>Member since</small><b><?= e(substr($u['created_at'], 0, 10)) ?></b></div>
</div>

<div class="stats">
  <div class="card stat"><small>Books on loan</small><b><?= (int)$onLoan ?></b></div>
  <div class="card stat"><small>Total loans</small><b><?= count($loans) ?></b></div>
  <div class="card stat <?= $outstanding > 0 ? 'bad' : '' ?>"><small>Unpaid fines</small><b><?= money($outstanding) ?></b></div>
</div>

<h2>Borrowing history</h2>
<div class="card wrap">
  <table>
    <thead><tr><th>Book</th><th>Borrowed</th><th>Due date</th><th>Returned</th><th>Fine</th></tr></thead>
    <tbody>
    <?php foreach ($loans as $l): ?>
      <tr>
        <td><?= e($l['title']) ?></td><td><?= e(substr($l['borrowed_at'], 0, 10)) ?></td>
        <td><?= e($l['due_date']) ?><?php if ((int)$l['renewals'] > 0): ?> <span class="pill pr">renewed <?= (int)$l['renewals'] ?>x</span><?php endif; ?></td>
        <td><?= $l['returned_at'] ? e(substr($l['returned_at'], 0, 10)) : '<span class="pill ' . ($l['overdue'] ? 'dg">Overdue' : 'ok">On loan') . '</span>' ?></td>
        <td><?= money($l['fine']) ?>
          <?php if ($l['returned_at'] && (float)$l['fine_amount'] > 0): ?>
            <?= $l['balance'] > 0 ? '<span class="pill dg">Unpaid ' . e(money($l['balance'])) . '</span>' : '<span class="pill ok">Paid</span>' ?>
          <?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$loans): ?><tr><td colspan="5" class="muted">No loans yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<h2>Reservations</h2>
<div class="card wrap">
  <table>
    <thead><tr><th>Book</th><th>Reserved on</th><th>Place in line</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($reservations as $r): ?>
      <tr><td><?= e($r['title']) ?></td><td><?= e(substr($r['reserved_at'], 0, 10)) ?></td><td><?= (int)$r['place'] ?></td>
        <td><?= $r['status'] === 'ready' ? '<span class="pill ok">Ready until ' . e($r['expires_on']) . '</span>' : '<span class="pill pr">Waiting</span>' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$reservations): ?><tr><td colspan="4" class="muted">No open reservations.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<h2>Fine payments</h2>
<div class="card wrap">
  <table>
    <thead><tr><th>Date</th><th>Book</th><th>Amount</th><th>Method</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
      <tr><td><?= e(substr($p['paid_at'], 0, 10)) ?></td><td><?= e($p['title']) ?></td><td><?= money($p['amount']) ?></td><td><?= e(ucfirst($p['method'])) ?></td>
        <td class="actions"><a class="btn light sm" href="<?= url('fines/receipt/' . $p['id']) ?>">Receipt</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="5" class="muted">No payments recorded.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<p><a href="<?= url('members') ?>">&larr; Back to members</a></p>
