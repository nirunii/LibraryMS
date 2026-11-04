<?php /** Variables passed in by the Controller:
 * @var array $book
 * @var bool $mine
 */ ?>
<?php
  $guest = !user();
  $total = max(1, (int)$book['copies_total']);
  $held = (int)$book['held'];
  $free = max(0, (int)$book['copies_available'] - $held);
  $canBorrow = $free > 0 || $mine;
  $pct = (int)round($free / $total * 100);
  $next = 'auth/login&next=books/show/' . (int)$book['id'];
?>
<p><a href="<?= url('books') ?>">&larr; Back to books</a></p>
<div class="detail">
  <div class="cover big" style="background:<?= ui_cover_color($book['id']) ?>"><span><?= e($book['title']) ?></span><small><?= e($book['author']) ?></small></div>
  <div class="card">
    <h1><?= e($book['title']) ?></h1>
    <p class="by"><?= e($book['author']) ?></p>
    <div class="kv">
      <div><small>Category</small><b><?= e($book['category']) ?></b></div>
      <div><small>ISBN</small><b><?= e($book['isbn']) ?></b></div>
      <div><small>Copies owned</small><b><?= (int)$book['copies_total'] ?></b></div>
      <div><small>Available now</small><b><?= $free ?></b></div>
      <div><small>In the holds queue</small><b><?= (int)$book['waiting'] + $held ?></b></div>
    </div>
    <div class="meter detail-meter"><i class="<?= $free > 0 ? '' : 'none' ?>" style="width:<?= $pct ?>%"></i></div>
    <p class="avail <?= $free > 0 ? 'yes' : 'no' ?>"><?= $free > 0 ? $free . ' of ' . (int)$book['copies_total'] . ' available' : 'All copies are on loan' ?></p>
    <?php if ($mine): ?><p><span class="pill ok">A copy is being held for you</span></p><?php endif; ?>
    <div class="actions">
      <?php if ($guest): ?>
        <a class="btn <?= $free > 0 ? 'green' : 'light' ?>" href="<?= e(url($next)) ?>"><?= $free > 0 ? 'Log in to borrow' : 'Log in to reserve' ?></a>
        <span class="muted">Browsing is open to everyone. You need an account to borrow or reserve.</span>
      <?php elseif (is_staff()): ?>
        <a class="btn light" href="<?= url('books/edit/' . $book['id']) ?>">Edit this book</a>
      <?php elseif ($canBorrow): ?>
        <form method="post" action="<?= url('loans/borrow') ?>">
          <?= csrf_field() ?><input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>"><button class="btn green">Borrow</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= url('loans/reserve') ?>">
          <?= csrf_field() ?><input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>"><button class="btn light">Reserve</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
