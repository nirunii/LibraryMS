<?php /** Variables passed in by the Controller:
 * @var array $stats
 * @var array $myActive
 * @var array $monthly
 * @var array $top
 * @var float $myFines
 * @var int $myReady
 */ ?>
<?php
$hour  = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$first = explode(' ', trim(user()['name']))[0];
?>
<section class="hero">
  <div class="hero-text">
    <h1><?= e($greet) ?>, <?= e($first) ?></h1>
    <?php if (is_staff()): ?>
      <p><?= (int)$stats['Books on loan'] ?> books are on loan and <?= (int)$stats['Overdue'] ?> are overdue today.</p>
      <a class="btn green" href="<?= url('loans') ?>">Manage loans</a>
    <?php else: ?>
      <p>You have <?= count($myActive) ?> book<?= count($myActive) === 1 ? '' : 's' ?> on loan. Find your next read in the catalogue.</p>
      <a class="btn green" href="<?= url('books') ?>">Browse books</a>
    <?php endif; ?>
  </div>
  <div class="hero-art" aria-hidden="true">
    <span class="cover" style="background:<?= ui_cover_color('a') ?>"></span>
    <span class="cover" style="background:<?= ui_cover_color('b') ?>"></span>
    <span class="cover" style="background:<?= ui_cover_color('c') ?>"></span>
  </div>
</section>

<?php if (!is_staff()): ?>
  <?php if ($myReady > 0): ?><div class="alert success">A book you reserved is ready for you to borrow. <a href="<?= url('books') ?>">Go to Books</a></div><?php endif; ?>
  <?php if ($myFines > 0): ?><div class="alert error">You owe <?= money($myFines) ?> in unpaid fines. Please pay at the library desk.</div><?php endif; ?>
<?php endif; ?>

<?php if (is_staff()): ?>
  <?php $icons = ['Total book copies' => ['book', 't1'], 'Members' => ['users', 't2'], 'Books on loan' => ['loans', 't3'], 'Overdue' => ['alert', 't4'], 'Unpaid fines' => ['cash', 't2']]; ?>
  <div class="stats">
    <?php foreach ($stats as $label => $value): ?>
      <div class="card stat with-ico">
        <span class="ico <?= e($icons[$label][1]) ?>"><?= ui_icon($icons[$label][0]) ?></span>
        <div><small><?= e($label) ?></small><b><?= e($value) ?></b></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <h2>Your current loans</h2>
  <div class="card wrap">
    <table>
      <thead><tr><th>Book</th><th>Due date</th><th>Status</th><th>Fine so far</th></tr></thead>
      <tbody>
      <?php foreach ($myActive as $l): ?>
        <tr><td><?= e($l['title']) ?></td><td><?= e($l['due_date']) ?></td>
          <td><span class="pill <?= $l['overdue'] ? 'dg' : 'ok' ?>"><?= $l['overdue'] ? 'Overdue' : 'On time' ?></span></td>
          <td><?= money($l['fine']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$myActive): ?><tr><td colspan="4" class="muted">You have no books on loan.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<h2><?= is_staff() ? 'Books borrowed per month (whole library)' : 'Your books borrowed per month' ?></h2>
<div class="card"><canvas id="chart" height="110"></canvas></div>

<h2>Most borrowed books in the library</h2>
<div class="shelf">
  <?php foreach ($top as $t): ?>
    <div class="mini">
      <div class="cover" style="background:<?= ui_cover_color($t['title']) ?>"><span><?= e($t['title']) ?></span></div>
      <strong><?= e($t['title']) ?></strong>
      <small><?= e($t['author']) ?></small>
      <em><?= (int)$t['times_borrowed'] ?> loans</em>
    </div>
  <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  var labels = <?= json_encode(array_column($monthly, 'label')) ?>;
  var totals = <?= json_encode(array_map('intval', array_column($monthly, 'total'))) ?>;
  new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: { labels: labels, datasets: [{ label: 'Books borrowed', data: totals, backgroundColor: '#7a4aa6', borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
</script>
