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
        return $err;
    }

    public function create($d)
    {
        $st = $this->db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $st->execute([trim($d['name']), $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), $d['role'] ?? 'member']);
        return $this->db->lastInsertId();
    }

    public function update($id, $d)
    {
        $sql = 'UPDATE users SET name = ?, email = ?, role = ?';
        $p = [trim($d['name']), $d['email'], $d['role']];
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
        return $this->many("SELECT id, name, email FROM users WHERE role = 'member' ORDER BY name");
    }

    public function countMembers()
    {
        return (int)$this->scalar("SELECT COUNT(*) FROM users WHERE role = 'member'");
    }
}
