<?php
// Same "which layout am I in" test the header uses.
$fseg = explode('/', trim($_GET['url'] ?? '', '/'))[0];
$publicLayout = !user() && $fseg === 'books';
?>
  </main>
<?php if ($publicLayout): ?>
  <footer class="site-foot">LibraryMS &middot; PHP MVC Library Management System</footer>
<?php endif; ?>
</div>
</body>
</html>
