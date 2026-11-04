<?php /** Variables passed in by the Controller:
 * @var array $overdue
 * @var array $top
 * @var array $totals
 * @var int $rate
 */ ?>
<h1>Reports</h1>
<div class="stats">
  <div class="card stat bad"><small>Overdue books</small><b><?= count($overdue) ?></b></div>
  <div class="card stat"><small>Fines collected</small><b><?= money($totals['collected']) ?></b></div>
  <div class="card stat bad"><small>Unpaid fines</small><b><?= money($totals['outstanding']) ?></b></div>
  <div class="card stat"><small>Fines waived</small><b><?= money($totals['waived']) ?></b></div>
  <div class="card stat"><small>Fine rate</small><b><?= money($rate) ?>/day</b></div>
</div>

<h2>Overdue books</h2>
<div class="card wrap">
  <table>
    <thead><tr><th>Book</th><th>Member</th><th>Due date</th><th>Days overdue</th><th>Fine so far</th></tr></thead>
    <tbody>
    <?php foreach ($overdue as $o): ?>
      <tr><td><?= e($o['title']) ?></td><td><?= e($o['member']) ?></td><td><?= e($o['due_date']) ?></td>
        <td><?= (int)$o['days_overdue'] ?></td><td><?= money($o['fine']) ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$overdue): ?><tr><td colspan="5" class="muted">No overdue books.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<h2>Most borrowed books</h2>
<div class="card wrap">
  <table>
    <thead><tr><th>#</th><th>Title</th><th>Author</th><th>Times borrowed</th></tr></thead>
    <tbody>
    <?php foreach ($top as $i => $t): ?>
      <tr><td><?= $i + 1 ?></td><td><?= e($t['title']) ?></td><td><?= e($t['author']) ?></td><td><?= e($t['times_borrowed']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
