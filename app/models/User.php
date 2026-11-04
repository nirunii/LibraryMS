<?php
// MODEL: users (admins, librarians, members). Holds validation and data access.
class User extends Model
{
    const ROLES = ['admin', 'librarian', 'member'];

    public function find($id)
    {
        return $this->one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail($email)
    {
        return $this->one('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function authenticate($email, $password)
    {
        $u = $this->findByEmail($email);
        return ($u && password_verify($password, $u['password'])) ? $u : false;
    }

    // Returns an array of error messages (empty array = valid).
    public function validate($d, $id = null)
    {
        $err = [];
        if (trim($d['name'] ?? '') === '') { $err[] = 'Name is required.'; }
        if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $err[] = 'Enter a valid email address.';
        } else {
            $other = $this->findByEmail($d['email']);
            if ($other && (int)$other['id'] !== (int)$id) { $err[] = 'That email is already registered.'; }
        }
        $pw = $d['password'] ?? '';
        if (($id === null || $pw !== '') && strlen($pw) < 6) { $err[] = 'Password must be at least 6 characters.'; }
        if (isset($d['role']) && !in_array($d['role'], self::ROLES, true)) { $err[] = 'Invalid role.'; }
        if (strlen(trim($d['phone'] ?? '')) > 30) { $err[] = 'Phone number is too long.'; }
        return $err;
    }

    public function create($d)
    {
        $st = $this->db->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
        $st->execute([trim($d['name']), $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), trim($d['phone'] ?? '') ?: null, $d['role'] ?? 'member']);
        return $this->db->lastInsertId();
    }

    public function update($id, $d)
    {
        $sql = 'UPDATE users SET name = ?, email = ?, phone = ?, role = ?';
        $p = [trim($d['name']), $d['email'], trim($d['phone'] ?? '') ?: null, $d['role']];
        if (($d['password'] ?? '') !== '') {
            $sql .= ', password = ?';
            $p[] = password_hash($d['password'], PASSWORD_DEFAULT);
        }
        $p[] = $id;
        $this->db->prepare($sql . ' WHERE id = ?')->execute($p);
    }

    public function delete($id)
    {
        $this->db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    public function all($search = '')
    {
        $like = '%' . $search . '%';
        return $this->many(
            'SELECT u.*, (SELECT COUNT(*) FROM loans l WHERE l.user_id = u.id AND l.returned_at IS NULL) AS active_loans
             FROM users u WHERE u.name LIKE ? OR u.email LIKE ? ORDER BY u.role, u.name',
            [$like, $like]
        );
    }

    public function membersOnly()
    {
        return $this->many("SELECT id, name, email FROM users WHERE role = 'member' AND status = 'active' ORDER BY name");
    }

    public function countMembers()
    {
        return (int)$this->scalar("SELECT COUNT(*) FROM users WHERE role = 'member'");
    }

    // Members change their own name, email and phone (not their role).
    public function updateProfile($id, $d)
    {
        $this->db->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?')
            ->execute([trim($d['name']), $d['email'], trim($d['phone'] ?? '') ?: null, $id]);
    }

    // Returns [success, message]
    public function changePassword($id, $current, $new)
    {
        $u = $this->find($id);
        if (!$u || !password_verify($current, $u['password'])) { return [false, 'Your current password is not correct.']; }
        if (strlen($new) < 6) { return [false, 'The new password must be at least 6 characters.']; }
        $this->db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
        return [true, 'Password changed.'];
    }

    // Suspended members can log in but cannot borrow.
    public function setStatus($id, $status)
    {
        if (!in_array($status, ['active', 'suspended'], true)) { return; }
        $this->db->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$status, $id]);
    }
}
