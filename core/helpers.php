<?php
function url($path = '')   { return BASE_URL . '/index.php?url=' . ltrim($path, '/'); }
function asset($path)
{
    // Append the file's modified time so browsers never keep serving a stale stylesheet.
    $file = __DIR__ . '/../public/' . $path;
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return BASE_URL . '/public/' . $path . $v;
}
function e($value)         { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money($value)     { return 'Rs. ' . number_format((float)$value, 2); }
function member_no($id)    { return 'LIB' . str_pad((string)(int)$id, 4, '0', STR_PAD_LEFT); }
function user()            { return $_SESSION['user'] ?? null; }
function is_staff()        { $r = $_SESSION['user']['role'] ?? ''; return $r === 'admin' || $r === 'librarian'; }
function csrf_field() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

// ---- UI helpers (presentation only, used by the Views) ----
function ui_initials($name) {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $w) { $out .= strtoupper(substr($w, 0, 1)); }
    return $out;
}
function ui_icon($name) {
    $paths = [
        'home'   => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'book'   => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'loans'  => '<polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'users'  => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'chart'  => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'alert'  => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'cash'   => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'user'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'audit'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'sliders' => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}
// Book "cover" colour, chosen from the id or title so a book always gets the same colour.
function ui_cover_color($seed) {
    $pairs = [['#7a4aa6', '#5b3585'], ['#1b7f4f', '#14603b'], ['#d9485f', '#a82f44'], ['#2b6cb0', '#1f4f85'],
              ['#e08a1e', '#b56a10'], ['#2f9e9e', '#217474'], ['#8a4fbf', '#6a3a97'], ['#4a5568', '#2d3748']];
    $p = $pairs[abs(crc32((string)$seed)) % count($pairs)];
    return 'linear-gradient(145deg,' . $p[0] . ',' . $p[1] . ')';
}
