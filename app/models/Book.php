<?php
// MODEL: books. CRUD, search and stock rules.
class Book extends Model
{
    public function find($id)
    {
        return $this->one('SELECT * FROM books WHERE id = ?', [$id]);
    }

    public function all($q = '', $category = '')
    {
        $sql = 'SELECT * FROM books WHERE 1 = 1';
        $p = [];
        if ($q !== '') {
            $sql .= ' AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)';
            $like = '%' . $q . '%';
            array_push($p, $like, $like, $like);
        }
        if ($category !== '') {
            $sql .= ' AND category = ?';
            $p[] = $category;
        }
        return $this->many($sql . ' ORDER BY title', $p);
    }

    public function categories()
    {
        $st = $this->db->query('SELECT DISTINCT category FROM books ORDER BY category');
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    public function validate($d, $id = null)
    {
        $err = [];
        foreach (['isbn' => 'ISBN', 'title' => 'Title', 'author' => 'Author', 'category' => 'Category'] as $f => $label) {
            if (trim($d[$f] ?? '') === '') { $err[] = $label . ' is required.'; }
        }
        $copies = $d['copies_total'] ?? '';
        if (!ctype_digit((string)$copies) || (int)$copies < 1) { $err[] = 'Copies must be a whole number of 1 or more.'; }
        if (trim($d['isbn'] ?? '') !== '') {
            $other = $this->one('SELECT id FROM books WHERE isbn = ?', [trim($d['isbn'])]);
            if ($other && (int)$other['id'] !== (int)$id) { $err[] = 'A book with that ISBN already exists.'; }
        }
        if ($id !== null && !$err) {
            $b = $this->find($id);
            if ($b['copies_available'] + ((int)$copies - $b['copies_total']) < 0) {
                $err[] = 'Cannot reduce copies below the number currently on loan.';
            }
        }
        return $err;
    }

    public function create($d)
    {
        $st = $this->db->prepare('INSERT INTO books (isbn, title, author, category, copies_total, copies_available) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([trim($d['isbn']), trim($d['title']), trim($d['author']), trim($d['category']), (int)$d['copies_total'], (int)$d['copies_total']]);
    }

    public function update($id, $d)
    {
        $b = $this->find($id);
        $newTotal = (int)$d['copies_total'];
        $avail = $b['copies_available'] + ($newTotal - $b['copies_total']);
        $st = $this->db->prepare('UPDATE books SET isbn = ?, title = ?, author = ?, category = ?, copies_total = ?, copies_available = ? WHERE id = ?');
        $st->execute([trim($d['isbn']), trim($d['title']), trim($d['author']), trim($d['category']), $newTotal, $avail, $id]);
    }

    public function delete($id)
    {
        $this->db->prepare('DELETE FROM books WHERE id = ?')->execute([$id]);
    }

    public function countAll()
    {
        return (int)$this->scalar('SELECT COALESCE(SUM(copies_total), 0) FROM books');
    }

    public function topBorrowed($limit = 5)
    {
        $limit = (int)$limit;
        return $this->many(
            "SELECT b.title, b.author, COUNT(l.id) AS times_borrowed
             FROM books b JOIN loans l ON l.book_id = b.id
             GROUP BY b.id, b.title, b.author ORDER BY times_borrowed DESC LIMIT $limit"
        );
    }
}
