<?php

namespace Hexlet\Code;
use Valitron\Validator;

/**
 * Создание в записи в таблице urls
 */
class PostgreSQLAddData {

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
     * добавление значений в таблицу urls
     */
    public function insertUrl($name): array
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

    /**
     * добавление значений в таблицу url_checks
     */
    public function addCheck($id): array
    {
        // подготовка запроса для добавления данных
        $v = new Validator(array('id' => $id));
        $v->rule('integer', 'id');

        if($v->validate()) {
            $sql = 'INSERT INTO url_checks(url_id, created_at) VALUES(:id, NOW())';
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(':id', $id);

            $stmt->execute();

            return ['success' => [
                'id' => $id,
                'check' => $this->pdo->lastInsertId('url_checks_id_seq'),
            ]];
        } else {
            return ['errors' => $v->errors()];
        }
    }
}