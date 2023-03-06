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
        $validator = new Validator(['name' => $name]);
        $validator->rule('required', 'name')->message('URL не должен быть пустым')->label('Name');
        $validator->rule('lengthMax', 'name', 256)->message('Слишком длинный адрес')->label('Name');
        $validator->rule('url', 'name')->message('Некорректный URL')->label('Name');

        if ($validator->validate()) {
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
            return ['errors' => [$validator->errors()['name'][0] ?? '']];
        }
    }

    /**
     * добавление значений в таблицу url_checks
     */
    public function addCheck(array $pageData): array
    {
        $validator = new Validator(array('id' => $pageData['url_id']));
        $validator->rule('integer', 'id');

        if ($validator->validate()) {
            $sql = 'INSERT INTO url_checks(url_id, status_code, h1, title, description, created_at) 
                    VALUES(:id, :status_code, :h1, :title, :description, NOW())';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $pageData['url_id']);
            $stmt->bindValue(':status_code', $pageData['status_code']);
            $stmt->bindValue(':h1', substr(htmlspecialchars($this->encodeBinder($pageData['h1'])), 0, 255));
            $stmt->bindValue(':title', substr(htmlspecialchars($this->encodeBinder($pageData['title'])), 0, 255));
            $stmt->bindValue(':description', substr(htmlspecialchars($this->encodeBinder($pageData['description'])), 0, 255));
            $stmt->execute();

            return ['success' => [
                'id' => $pageData['url_id'],
                'check' => $this->pdo->lastInsertId('url_checks_id_seq'),
            ]];
        } else {
            return ['errors' => $validator->errors()];
        }
    }

    public function encodeBinder(string $text): string
    {
        return mb_convert_encoding(
            $text,
            "UTF-8",
            !empty($detect = mb_detect_encoding($text)) ? $detect : null
        );
    }
}
