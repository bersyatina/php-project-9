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
        // подготовка запроса для добавления данных
        $sql = 'SELECT * FROM urls';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUrl($id): array
    {
        // подготовка запроса для добавления данных
        $sql = 'SELECT * FROM urls WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}