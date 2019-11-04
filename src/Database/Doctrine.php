<?php

namespace App\Database;

use Doctrine\DBAL\Connection;

class Doctrine
{
    public function __construct(
        Connection $connection
    ) {
        static::getConnection($connection);
    }

    public static function getConnection(
        Connection $connection = null
    ): Connection {
        static $conn = null;

        if (is_null($conn)) {
            $conn = $connection;
        }

        return $conn;
    }
}
