<?php

namespace App;

class DBConnection
{
    private static $connection = null;
    public static function connection()
    {
        if (self::$connection === null) {
            $connectionParams = [
                'dbname' => 'friends',
                'user' => 'kristaps',
                'password' => 'zxc12345',
                'host' => 'localhost',
                'driver' => 'pdo_mysql',
            ];
            self::$connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

        }
        return self::$connection;
    }
}

