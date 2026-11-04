<?php /** Variables passed in by the Controller:
 * @var array $unpaid
 * @var array $recent
 * @var array $totals
 * @var bool $isAdmin
 */ ?>
<h1>Fines</h1>
<div class="stats">
  <div class="card stat bad"><small>Unpaid fines</small><b><?= money($totals['outstanding']) ?></b></div>
  <div class="card stat"><small>Collected so far</small><b><?= money($totals['collected']) ?></b></div>
  <div class="card stat"><small>Waived</small><b><?= money($totals['waived']) ?></b></div>
</div>

<h2>Fines waiting to be paid</h2>
<div class="card wrap">
  <table>
    <thead><tr><th>Member</th><th>Book</th><th>Fine</th><th>Paid</th><th>Balance</th><th>Record a payment</th></tr></thead>
    <tbody>
    <?php foreach ($unpaid as $f): ?>
      <tr>
        <td><a href="<?= url('members/show/' . $f['user_id']) ?>"><?= e($f['member']) ?></a></td>
        <td><?= e($f['title']) ?></td>
        <td><?= money($f['fine_amount']) ?></td><td><?= money($f['paid']) ?></td>
        <td><b><?= money($f['balance']) ?></b></td>
        <td>
          <form method="post" action="<?= url('fines/pay/' . $f['id']) ?>" class="payform">
            <?= csrf_field() ?>
            <input name="amount" value="<?= e(number_format((float)$f['balance'], 2, '.', '')) ?>" aria-label="Amount" required>
            <select name="method" aria-label="Method">
              <option value="cash">Cash</option><option value="card">Card</option><option value="online">Online</option>
              <?php if ($isAdmin): ?><option value="waived">Waive fine</option><?php endif; ?>
            </select>
            <input name="note" placeholder="Note (optional)" aria-label="Note">
            <button class="btn sm">Record</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$unpaid): ?><tr><td colspan="6" class="muted">No unpaid fines.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<p class="muted">A fine can be paid once the book is returned. You can take a part payment; the rest stays as the balance.<?= $isAdmin ? '' : ' Only an admin can waive a fine.' ?></p>

<h2>Recent payments</h2>
<div class="card wrap">
  <table>
    <thead><tr><th>Date</th><th>Member</th><th>Book</th><th>Amount</th><th>Method</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($recent as $p): ?>
      <tr><td><?= e(substr($p['paid_at'], 0, 10)) ?></td><td><?= e($p['member']) ?></td><td><?= e($p['title']) ?></td>
        <td><?= money($p['amount']) ?></td><td><?= e(ucfirst($p['method'])) ?></td>
        <td class="actions"><a class="btn light sm" href="<?= url('fines/receipt/' . $p['id']) ?>">Receipt</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="6" class="muted">No payments yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
