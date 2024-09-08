<?php declare(strict_types=1);

namespace Az\Session\Driver;

use SessionHandlerInterface;

final class ArrayDriver implements SessionHandlerInterface
{
    private array $data = [];

    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        return $this->data[$id] ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $this->data[$id] = $data;
        return true;
    }

    public function destroy(string $id): bool
    {
        unset($this->data[$id]);
        return true;
    }

    public function gc(int $maxlifetime): int|false
    {        
        return false;
    }

    public function close(): bool
    {
        return true;
    }

    public function delete(string $id, int $maxlifetime)
    {
        
        // return $sth->rowCount();
    }
}
