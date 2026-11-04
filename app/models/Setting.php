<?php
// MODEL: library settings (loan period, fine rate, limits). Stored in the settings table.
// If the table is missing or a value is not set, the defaults from config/config.php are used.
class Setting extends Model
{
    private static $cache = null;

    // key => [label, default, min, max, help text]
    public static function definitions()
    {
        return [
            'loan_days'        => ['Loan period (days)', LOAN_DAYS, 1, 90, 'How long a member may keep a book.'],
            'fine_per_day'     => ['Fine per overdue day (Rs.)', FINE_PER_DAY, 0, 10000, 'Charged for every day a book is late.'],
            'max_loans'        => ['Maximum books per member', MAX_LOANS, 1, 20, 'Books a member may have on loan at once.'],
            'max_renewals'     => ['Renewals allowed per loan', 2, 0, 10, 'Set to 0 to switch renewals off.'],
            'renew_days'       => ['Days added by each renewal', 7, 1, 60, 'Added to the current due date.'],
            'hold_pickup_days' => ['Days a reserved book is held', 3, 1, 30, 'After this the hold expires and the next member is served.'],
            'fine_block_limit' => ['Block borrowing above unpaid fines of (Rs.)', 500, 0, 100000, 'A member owing more than this cannot borrow until they pay.'],
        ];
    }

    private static function load()
    {
        if (self::$cache !== null) { return; }
        self::$cache = [];
        try {
            foreach (Database::get()->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $r) {
                self::$cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) {
            // settings table not available: fall back to defaults
        }
    }

    public static function get($key)
    {
        self::load();
        $defs = self::definitions();
        $default = isset($defs[$key]) ? $defs[$key][1] : null;
        return (int)(self::$cache[$key] ?? $default);
    }

    // All settings as key => current value
    public function all()
    {
        $out = [];
        foreach (self::definitions() as $key => $def) { $out[$key] = self::get($key); }
        return $out;
    }

    // Returns an array of error messages (empty = valid).
    public function validate($input)
    {
        $err = [];
        foreach (self::definitions() as $key => $def) {
            $v = trim((string)($input[$key] ?? ''));
            if ($v === '' || !ctype_digit($v) || (int)$v < $def[2] || (int)$v > $def[3]) {
                $err[] = $def[0] . ' must be a whole number between ' . $def[2] . ' and ' . $def[3] . '.';
            }
        }
        return $err;
    }

    // Saves the values and returns a short text describing what changed (for the audit log).
    public function save($input)
    {
        $changes = [];
        $st = $this->db->prepare('REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)');
        foreach (self::definitions() as $key => $def) {
            $new = (int)$input[$key];
            if ($new !== self::get($key)) { $changes[] = $key . ': ' . self::get($key) . ' -> ' . $new; }
            $st->execute([$key, $new]);
        }
        self::$cache = null;
        return $changes ? implode(', ', $changes) : 'no change';
    }
}
