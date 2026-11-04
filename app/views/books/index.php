<?php /** Variables passed in by the Controller:
 * @var array $books
 * @var array $categories
 * @var string $q
 * @var string $cat
 * @var array $myReady
 */ ?>
<?php if (!user()): ?>
<section class="hero">
  <div class="hero-text">
    <h1>Welcome to the library</h1>
    <p>Browse and search the catalogue as a guest. Log in to borrow, reserve and renew books.</p>
    <a class="btn green" href="<?= url('auth/login') ?>">Log in</a>
    <a class="btn light" href="<?= url('auth/register') ?>">Create account</a>
  </div>
  <div class="hero-art" aria-hidden="true">
    <span class="cover" style="background:<?= ui_cover_color('a') ?>"></span>
    <span class="cover" style="background:<?= ui_cover_color('b') ?>"></span>
    <span class="cover" style="background:<?= ui_cover_color('c') ?>"></span>
  </div>
</section>
<?php endif; ?>
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
  <?php
    $total = max(1, (int)$b['copies_total']);
    $held = (int)$b['held'];                                   // copies set aside for members at the front of the queue
    $free = max(0, (int)$b['copies_available'] - $held);       // copies anyone can borrow
    $mine = in_array((int)$b['id'], $myReady, true);           // a copy is being held for this member
    $canBorrow = $free > 0 || $mine;
    $pct = (int)round($free / $total * 100);
  ?>
  <article class="book">
    <a class="cardlink" href="<?= url('books/show/' . $b['id']) ?>">
      <div class="cover" style="background:<?= ui_cover_color($b['id']) ?>"><span><?= e($b['title']) ?></span><small><?= e($b['author']) ?></small></div>
      <h3><?= e($b['title']) ?></h3>
    </a>
    <p class="by"><?= e($b['author']) ?></p>
    <p class="meta"><span class="chip"><?= e($b['category']) ?></span><span class="isbn">ISBN <?= e($b['isbn']) ?></span></p>
    <div class="meter"><i class="<?= $free > 0 ? '' : 'none' ?>" style="width:<?= $pct ?>%"></i></div>
    <p class="avail <?= $free > 0 ? 'yes' : 'no' ?>"><?= $free ?> of <?= (int)$b['copies_total'] ?> available</p>
    <?php if ($mine): ?><p class="meta"><span class="pill ok">Held for you</span></p>
    <?php elseif ((int)$b['waiting'] + $held > 0): ?><p class="meta"><?= (int)$b['waiting'] + $held ?> in the holds queue</p><?php endif; ?>
    <div class="actions">
      <?php if (!user()): ?>
        <a class="btn <?= $free > 0 ? 'green' : 'light' ?> sm" href="<?= e(url('auth/login&next=books/show/' . $b['id'])) ?>"><?= $free > 0 ? 'Log in to borrow' : 'Log in to reserve' ?></a>
      <?php elseif (is_staff()): ?>
        <a class="btn light sm" href="<?= url('books/edit/' . $b['id']) ?>">Edit</a>
        <form method="post" action="<?= url('books/delete/' . $b['id']) ?>" onsubmit="return confirm('Delete this book?')">
          <?= csrf_field() ?><button class="btn danger sm">Delete</button>
        </form>
      <?php elseif ($canBorrow): ?>
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
