<?php /** Variables passed in by the Controller:
 * @var array $books
 * @var array $categories
 * @var string $q
 * @var string $cat
 */ ?>
<div class="page-head">
  <h1>Books</h1>
  <?php if (is_staff()): ?><a class="btn green" href="<?= url('books/create') ?>">+ Add book</a><?php endif; ?>
</div>
<form class="tools" method="get" action="<?= BASE_URL ?>/index.php">
  <input type="hidden" name="url" value="books">
  <input class="grow" name="q" value="<?= e($q) ?>" placeholder="Search by title, author or ISBN">
  <select name="cat">
    <option value="">All categories</option>
    <?php foreach ($categories as $c): ?>
      <option <?= $c === $cat ? 'selected' : '' ?>><?= e($c) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn green">Search</button>
</form>

<div class="books">
<?php foreach ($books as $b): ?>
  <?php $total = max(1, (int)$b['copies_total']); $free = (int)$b['copies_available']; $pct = (int)round($free / $total * 100); ?>
  <article class="book">
    <div class="cover" style="background:<?= ui_cover_color($b['id']) ?>"><span><?= e($b['title']) ?></span><small><?= e($b['author']) ?></small></div>
    <h3><?= e($b['title']) ?></h3>
    <p class="by"><?= e($b['author']) ?></p>
    <p class="meta"><span class="chip"><?= e($b['category']) ?></span><span class="isbn">ISBN <?= e($b['isbn']) ?></span></p>
    <div class="meter"><i class="<?= $free > 0 ? '' : 'none' ?>" style="width:<?= $pct ?>%"></i></div>
    <p class="avail <?= $free > 0 ? 'yes' : 'no' ?>"><?= $free ?> of <?= (int)$b['copies_total'] ?> available</p>
    <div class="actions">
      <?php if (is_staff()): ?>
        <a class="btn light sm" href="<?= url('books/edit/' . $b['id']) ?>">Edit</a>
        <form method="post" action="<?= url('books/delete/' . $b['id']) ?>" onsubmit="return confirm('Delete this book?')">
          <?= csrf_field() ?><button class="btn danger sm">Delete</button>
        </form>
      <?php elseif ($free > 0): ?>
        <form method="post" action="<?= url('loans/borrow') ?>">
          <?= csrf_field() ?><input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>"><button class="btn green sm">Borrow</button>
        </form>
      <?php else: ?>
        <form method="post" action="<?= url('loans/reserve') ?>">
          <?= csrf_field() ?><input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>"><button class="btn light sm">Reserve</button>
        </form>
      <?php endif; ?>
    </div>
  </article>
<?php endforeach; ?>
</div>
<?php if (!$books): ?><div class="card muted">No books match your search.</div><?php endif; ?>
