<?php

namespace Az\Session\Driver;

use SessionHandlerInterface;
use \PDO;

final class Db implements SessionHandlerInterface
{
    const CREATE_TABLE_SESSIONS = "CREATE TABLE `sessions` (
        `id` varbinary(192) NOT NULL,
        `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        PRIMARY KEY (`id`),
        KEY `last_activity` (`last_activity`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $sql = "SELECT data FROM sessions WHERE id = ? LIMIT 1";
        $sth = $this->pdo->prepare($sql);
        $sth->setFetchMode(PDO::FETCH_NUM);
        $sth->execute([$id]);
        $data = $sth->fetchColumn();

        return ($data) ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        $sql = "REPLACE INTO sessions VALUES (?, NOW(), ?)";
        $sth = $this->pdo->prepare($sql);
        $sth->execute([$id, $data]);
        return true;
    }

    public function destroy(string $id): bool
    {
        $sql = "DELETE FROM sessions WHERE id = ?";
        $sth = $this->pdo->prepare($sql);
        $sth->execute([$id]);
        return true;
    }

    public function gc(int $maxlifetime): int|false
    {
        $sql = "DELETE FROM sessions WHERE last_activity < (NOW() - INTERVAL ? SECOND)";
        $sth = $this->pdo->prepare($sql);
        $sth->execute([(int) $maxlifetime]);

        return $sth->rowCount();
    }

    public function close(): bool
    {
        return true;
    }

    public function delete(string $id, int $maxlifetime)
    {
        $sql = "DELETE FROM sessions WHERE last_activity < (NOW() - INTERVAL ? SECOND) AND id = ?";
        $sth = $this->pdo->prepare($sql);
        $sth->execute([(int) $maxlifetime, $id]);

        return $sth->rowCount();
    }
}
