<?php

namespace Hexlet\Code;
use Valitron\Validator;

/**
 * Создание в записи в таблице urls
 */
class PostgreSQLAddUrl {

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
    public function insertUrl($name)
    {
        // подготовка запроса для добавления данных
        $v = new Validator(array('name' => $name));
        $v->rules([
            'lengthMax' => [
                ['name', 256]
            ],
            'lengthMin' => [
                ['name', 5]
            ],
            'required' => [
                ['name']
            ]
        ]);

        if($v->validate()) {
            $sql = 'INSERT INTO urls(name, created_at) VALUES(:name, NOW())';
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(':name', $name);

            $stmt->execute();

            return ['success' => [
                'name' => $name,
                'id' => $this->pdo->lastInsertId('urls_id_seq'),
            ]];
        } else {
            return ['errors' => $v->errors()];
        }
    }
}