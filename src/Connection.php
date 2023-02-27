<?php

namespace Hexlet\Code;

/**
 * Создание класса Connection
 */
final class Connection
{
    /**
     * Connection
     * тип @var
     */
    private static ?Connection $conn = null;

    protected function __construct()
    {
    }

    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return \PDO
     * @throws \Exception
     */
    public function connect()
    {
        $params = array_key_exists('DATABASE_URL', $_ENV)
            ? parse_url($_ENV['DATABASE_URL'])
            : parse_ini_file('database.ini');

        if ($params === false) {
            throw new \Exception("Error reading database configuration file");
        }

        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            ltrim($params['path'], '/'),
            $params['user'],
            $params['pass'],
        );

        $pdo = new \PDO($conStr);
        
        try {
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }

        return $pdo;
    }

    /**
     * возврат экземпляра объекта Connection
     * тип @return
     */
    public static function get()
    {
        if (null === static::$conn) {
            static::$conn = new self();
        }

        return static::$conn;
    }
}
