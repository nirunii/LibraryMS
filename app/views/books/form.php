<?php /** Variables passed in by the Controller:
 * @var array $book
 * @var array $errors
 * @var string $action
 * @var string $title
 */ ?>
<div class="card narrow">
  <h1><?= e($title) ?></h1>
  <?php foreach ($errors as $er): ?><div class="alert error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" action="<?= url($action) ?>">
    <?= csrf_field() ?>
    <label>ISBN <input name="isbn" value="<?= e($book['isbn']) ?>" required></label>
    <label>Title <input name="title" value="<?= e($book['title']) ?>" required></label>
    <label>Author <input name="author" value="<?= e($book['author']) ?>" required></label>
    <label>Category <input name="category" value="<?= e($book['category']) ?>" required></label>
    <label>Number of copies <input type="number" min="1" name="copies_total" value="<?= e($book['copies_total']) ?>" required></label>
    <button class="btn">Save</button>
    <a class="btn light" href="<?= url('books') ?>">Cancel</a>
  </form>
</div>
