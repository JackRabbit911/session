<?php

namespace Az\Session\Driver;

final class Files
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/') . '/';
    }

    public function delete(string $id, int $maxlifetime)
    {
        $file = $this->path . 'sess_' . $id;

        if (!is_file($file)) {
            return false;
        }

        if (fileatime($file) < (time() - $maxlifetime)) {
            unlink($file);
            return 1;
        }

        return 0;
    }
}
