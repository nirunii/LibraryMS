<?php
// Base class for all models.
abstract class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::get();
    }

    protected function one($sql, $params = [])
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetch();
    }

    protected function many($sql, $params = [])
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    protected function scalar($sql, $params = [])
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }
}
