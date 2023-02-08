<?php

namespace Hexlet\Code;
/**
 * Создание в записи в таблице urls
 */
class PostgreSQLAddValues {

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
     * добавление значений
     */
    public function insertUrl($name) {
        // подготовка запроса для добавления данных
        $sql = 'INSERT INTO urls(name) VALUES(:name)';
        $stmt = $this->pdo->prepare($sql);

        $stmt->bindValue(':name', $name);

        $stmt->execute();

        // возврат полученного значения id
        return $this->pdo->lastInsertId('urls_id_seq');
    }
}