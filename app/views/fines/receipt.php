<?php /** Variables passed in by the Controller:
 * @var array $p
 * @var float|null $balance
 */ ?>
<div class="card receipt">
  <div class="receipt-head">
    <div><h1>Payment receipt</h1><small>LibraryMS</small></div>
    <div class="right"><b>Receipt no. R<?= str_pad((string)(int)$p['id'], 5, '0', STR_PAD_LEFT) ?></b><br><small><?= e($p['paid_at']) ?></small></div>
  </div>
  <div class="kv">
    <div><small>Member</small><b><?= e($p['member']) ?></b></div>
    <div><small>Membership no.</small><b><?= e(member_no($p['user_id'])) ?></b></div>
    <div><small>Book</small><b><?= e($p['title']) ?></b></div>
    <div><small>Due date</small><b><?= e($p['due_date']) ?></b></div>
    <div><small>Returned</small><b><?= e(substr((string)$p['returned_at'], 0, 10)) ?></b></div>
    <div><small>Total fine</small><b><?= money($p['fine_amount']) ?></b></div>
    <div><small>Payment method</small><b><?= e($p['method'] === 'waived' ? 'Fine waived' : ucfirst($p['method'])) ?></b></div>
    <div><small>Received by</small><b><?= e($p['staff_name'] ?: '-') ?></b></div>
  </div>
  <div class="receipt-total">
    <span><?= $p['method'] === 'waived' ? 'Amount waived' : 'Amount paid' ?></span><b><?= money($p['amount']) ?></b>
  </div>
  <?php if ($balance !== null): ?><p class="muted">Balance still owing on this fine: <b><?= money(max(0, $balance)) ?></b></p><?php endif; ?>
  <?php if (!empty($p['note'])): ?><p class="muted">Note: <?= e($p['note']) ?></p><?php endif; ?>
  <p class="actions no-print">
    <button class="btn" onclick="window.print()">Print receipt</button>
    <a class="btn light" href="<?= url(is_staff() ? 'fines' : 'loans') ?>">Back</a>
  </p>
</div>
