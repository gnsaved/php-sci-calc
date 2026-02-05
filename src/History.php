<?php
namespace App\Storage;

class History
{
    private $db;

    public function __construct($dbPath)
    {
        $this->db = new \PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->createTable();
    }

    private function createTable()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS calculations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                expression TEXT NOT NULL,
                result TEXT NOT NULL,
                mode TEXT DEFAULT 'basic',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function save($expression, $result, $mode = 'basic')
    {
        $stmt = $this->db->prepare("
            INSERT INTO calculations (expression, result, mode) 
            VALUES (:expr, :result, :mode)
        ");
        
        $stmt->execute([
            ':expr' => $expression,
            ':result' => (string)$result,
            ':mode' => $mode
        ]);

        // keep only last 200 entries
        $this->db->exec("
            DELETE FROM calculations 
            WHERE id NOT IN (
                SELECT id FROM calculations ORDER BY id DESC LIMIT 200
            )
        ");
    }

    public function recent($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT expression, result, mode, created_at 
            FROM calculations 
            ORDER BY id DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function clear()
    {
        $this->db->exec("DELETE FROM calculations");
    }

    public function getByMode($mode, $limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT expression, result, created_at 
            FROM calculations 
            WHERE mode = :mode 
            ORDER BY id DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':mode', $mode);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function count()
    {
        return $this->db->query("SELECT COUNT(*) FROM calculations")->fetchColumn();
    }
}
