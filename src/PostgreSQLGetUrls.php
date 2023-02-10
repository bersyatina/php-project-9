<?php

namespace Hexlet\Code;
use PDO;
use Valitron\Validator;

/**
 * Запрос urls
 */
class PostgreSQLGetUrls {

    /**
     * объект PDO
     * @var \PDO
     */
    private $pdo;

    /**
     * инициализация объекта с объектом \PDO
     * @тип параметра $pdo
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * запрос всех значений таблицы
     */
    public function getUrls(): array
    {
        $sql = 'SELECT * FROM urls ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUrl($id): array
    {
        $sql = 'SELECT * FROM urls WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUrlByName($name): array|bool
    {
        $sql = 'SELECT * FROM urls WHERE name = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getChecks($id): array|bool
    {
        $sql = 'SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastCheck($id): array|bool
    {
        $sql = 'SELECT created_at FROM url_checks WHERE url_id = ? ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}