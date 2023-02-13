<?php

namespace Hexlet\Code;

use Valitron\Validator;

/**
 * Создание в записи в таблице urls
 */
class PostgreSQLAddData
{
    /**
     * объект PDO
     * @var \PDO
     */
    private $pdo;

    /**
     * инициализация объекта с объектом \PDO
     * @тип параметра $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * добавление значений в таблицу urls
     */
    public function insertUrl($name): array| bool
    {
        // подготовка запроса для добавления данных
        $v = new Validator([
            'name' => $name
        ]);
        $v->rule('required', 'name')->message('URL не должен быть пустым')->label('Name');
        $v->rule('lengthMax', 'name', 256)->message('Слишком длинный адрес')->label('Name');
        $v->rule('url', 'name')->message('Некорректный URL')->label('Name');

        if ($v->validate()) {
            $containsValue = new PostgreSQLGetUrls($this->pdo);
            $containsValue = $containsValue->getUrlByName($name);

            if (empty($containsValue)) {
                $sql = 'INSERT INTO urls(name, created_at) VALUES(:name, NOW())';
                $stmt = $this->pdo->prepare($sql);

                $stmt->bindValue(':name', $name);

                $stmt->execute();
                $id = $this->pdo->lastInsertId('urls_id_seq');
                $msg = 'Страница успешно добавлена';
            } else {
                $id = $containsValue['id'];
                $msg = 'Страница уже существует';
            }

            return ['success' => [
                'message' => $msg,
                'id' => $id,
            ]];
        } else {
            foreach ($v->errors()['name'] as $error) {
                return ['errors' => [$error]];
            }
        }
    }

    /**
     * добавление значений в таблицу url_checks
     */
    public function addCheck(array $pageData): array
    {
        // подготовка запроса для добавления данных
        $v = new Validator(array('id' => $pageData['url_id']));
        $v->rule('integer', 'id');

        if ($v->validate()) {
            $sql = 'INSERT INTO url_checks(url_id, status_code, h1, title, description, created_at) 
                    VALUES(:id, :status_code, :h1, :title, :description, NOW())';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $pageData['url_id']);
            $stmt->bindValue(':status_code', $pageData['status_code']);
            $stmt->bindValue(':h1', $pageData['h1']);
            $stmt->bindValue(':title', $pageData['title']);
            $stmt->bindValue(':description', $pageData['description']);
            $stmt->execute();

            return ['success' => [
                'id' => $pageData['url_id'],
                'check' => $this->pdo->lastInsertId('url_checks_id_seq'),
            ]];
        } else {
            return ['errors' => $v->errors()];
        }
    }
}