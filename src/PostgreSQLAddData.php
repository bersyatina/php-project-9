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
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * добавление значений в таблицу urls
     */
    public function insertUrl(string $name): array
    {
        // подготовка запроса для добавления данных
        $v = new Validator(['name' => $name]);
        $v->rule('required', 'name')->message('URL не должен быть пустым')->label('Name');
        $v->rule('lengthMax', 'name', 256)->message('Слишком длинный адрес')->label('Name');
        $v->rule('url', 'name')->message('Некорректный URL')->label('Name');

        if ($v->validate()) {
            $url = parse_url($name);
            $host = $url['host'];

            $containsValue = new PostgreSQLGetUrls($this->pdo);
            $containsValue = $containsValue->getUrlByName($host);

            if (empty($containsValue)) {
                $sql = 'INSERT INTO urls(name, created_at) VALUES(:name, NOW())';
                $stmt = $this->pdo->prepare($sql);

                $stmt->bindValue(':name', $host);

                $stmt->execute();
                $id = $this->pdo->lastInsertId('urls_id_seq');
                $msg = 'Страница успешно добавлена';
            } elseif (is_array($containsValue)) {
                $id = $containsValue['id'];
                $msg = 'Страница уже существует';
            }
            return ['success' => ['message' => $msg ?? '', 'id' => $id ?? false]];
        } else {
            return ['errors' => [$v->errors()['name'][0] ?? '']];
        }
    }

    /**
     * добавление значений в таблицу url_checks
     */
    public function addCheck(array $pageData): array
    {
        $v = new Validator(array('id' => $pageData['url_id']));
        $v->rule('integer', 'id');

        if ($v->validate()) {
            $sql = 'INSERT INTO url_checks(url_id, status_code, h1, title, description, created_at) 
                    VALUES(:id, :status_code, :h1, :title, :description, NOW())';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $pageData['url_id']);
            $stmt->bindValue(':status_code', $pageData['status_code']);
            $this->encodeBinder($stmt, 'h1' ,$pageData['h1']);
            $this->encodeBinder($stmt, 'title' ,$pageData['title']);
            $this->encodeBinder($stmt, 'description' ,$pageData['description']);
            $stmt->execute();

            return ['success' => [
                'id' => $pageData['url_id'],
                'check' => $this->pdo->lastInsertId('url_checks_id_seq'),
            ]];
        } else {
            return ['errors' => $v->errors()];
        }
    }

    public function encodeBinder(object $stmt, string $name, string $text): object
    {
        $stmt->bindValue(":{$name}", mb_convert_encoding(
            $text,
            "UTF-8",
            !empty($detect = mb_detect_encoding($text)) ? $detect : null
        ));
        return $stmt;
    }
}
