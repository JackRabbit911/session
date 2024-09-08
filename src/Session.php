<?php

namespace Az\Session;

use SessionHandlerInterface;

final class Session implements SessionInterface
{
    use OptionsTrait;

    private array $cookie = [       
        'lifetime'  => 3600,
        'path'      => '/',
        'domain'    => null,
        'secure'    => false,
        'httponly'  => true,
    ];

    private array $options = [
        'name'          => 'SID',
        'save_path'     => STORAGE . 'sessions',
        'gc_maxlifetime' => 3600,
        'gc_probability' => 0,
        'gc_divisor'     => 100,
    ];

    private array $session = [];
    private int $regenerate = 20;
    private bool $guard_agent = false;
    private bool $guard_ip = false;

    private $saveHandler;

    public function __construct(?array $options = null, ?SessionHandlerInterface $save_handler = null)
    {
        $this->options($options);

        if ($save_handler) {
            session_set_save_handler($save_handler, true);
        }

        $this->saveHandler = $save_handler ?? new Driver\Files($this->options['save_path']);

        if (isset($_COOKIE[$this->options['name']]) && (ctype_xdigit($sid = $_COOKIE[$this->options['name']]))) {
            $co = $this->saveHandler->delete($sid, $this->options['gc_maxlifetime']);
        }
    }

    public function id(?string $id = null): string
    {
        return session_id($id);
    }

    public function getCookieName()
    {
        return $this->options['name'];
    }

    public function init(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params($this->cookie);
        session_start($this->options);
       
        $this->session = & $_SESSION;
        
        if ($this->guard_agent === true) {
            $this->session['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? false;
        }

        if ($this->guard_ip === true) {
            $this->session['_remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? false;
        }

        $this->unset();

        if (isset($this->session['_flash'])) {
            $this->session['_delete'] = $this->session['_flash'];
            unset($this->session['_flash']);
        }

        if ($this->regenerate && !empty($this->session['user_id'])) {
            $now = time();

            if (!isset($this->session['_created'])) {
                $this->session['_created'] = $now;
            }
            
            if ($this->session['_created'] < $now - $this->regenerate) {
                $this->regenerate(true);
            }
        }
    }

    public function __set(string $key, $value): void
    {
        $this->set($key, $value);
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __isset($name)
    {
        return true;
    }

    public function regenerate(bool $delete_old_session = false): bool
    {
        $this->init();
        $_SESSION['_created'] = time();
        return session_regenerate_id($delete_old_session);
    }

    public function exists($key): bool
    {
        $this->init();
        return array_key_exists($key, $this->session);
    }

    public function get(?string $key = null, $default = null): mixed
    {
        $this->init();
        return ($key) ? $this->session[$key] ?? $default : $this->session ?? [];
    }

    public function pull(?string $key = null, $default = null): mixed
    {
        $result = $this->get($key, $default);
        $this->delete($key);
        return $result;
    }

    public function increment(string $key, int $step = 1): int
    {
        $this->init();
        if (!isset($this->session[$key])) {
            $this->session[$key] = 1;
        } else {
            $this->session[$key] = $this->session[$key] + $step;
        }

        return $this->session[$key];
    }

    public function decrement(string $key, int $step = 1): int
    {
        $this->init();
        if (!isset($this->session[$key])) {
            $this->session[$key] = 1;
        } else {
            $this->session[$key] = $this->session[$key] - $step;
        }

        return $this->session[$key];
    }

    public function set($key, $value = null): void
    {
        $this->init();
        $array = (is_array($key)) ? $key : [$key => $value];

        foreach ($array as $k => $value) {
            if (isset($this->session['_delete'][$k])) {
                unset($this->session['_delete'][$k]);
            }

            $this->session[$k] = $value;
        }
    }

    public function add($key, $value, $unique = true)
    {
        $this->init();

        if (!isset($this->session[$key])) {
            $array = [];
        } else {
            $array = (array) $this->session[$key];
        }

        $array = array_merge($array, (array) $value);
        
        if ($unique) {
            $array = array_unique($array);
        }

        $this->session[$key] = $array;
    }

    public function rm($key, $value)
    {
        $this->init();

        if (!isset($this->session[$key])) {
            return;
        }

        $k = array_search($value, $this->session[$key]);

        if ($k !== false) {
            unset($this->session[$key][$k]);
        }
    }

    public function flash($key, $value): void
    {
        $this->init();
        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $k => $value) {
            $this->session[$k] = $value;
            $this->keep($k);
        }
    }

    public function keep(string $key): mixed
    {
        $this->init();
        if (isset($this->session['_delete'])) {
            if (($k = array_search($key, $this->session['_delete'])) !== false) {
                unset($this->session['_delete'][$k]);                
            }

            if (empty($this->session['_delete'])) {
                unset($this->session['_delete']);
            }
        }

        if (array_key_exists($key, $this->session)) {
            $this->session['_flash'][] = $key;
            $this->session['_flash'] = array_unique($this->session['_flash']);
        }
        
        return $this->get($key);
    }

    public function delete(...$keys): void
    {
        $this->init();
        if (empty($this->session)) {
            return;
        }

        if (empty($keys) || $keys === [null]) {
            $this->session['_delete'] = true;
            return;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->session)) {
                $this->session['_delete'][] = $key;
            }
        }
    }

    public function remove($key)
    {
        $this->init();
        if (array_key_exists($key, $this->session)) {
            unset($this->session[$key]);
        }
    }

    public function commit(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            return false;
        }

        return session_write_close();
    }

    public function destroy(bool $killCookie = true): void
    {
        if (!headers_sent() && $killCookie === true) {
            setcookie($this->options['name'], '', -1, $this->cookie['path']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        if ($killCookie === false) {
            $this->init();
            $this->regenerate(true);
        }
    }

    public function gc(): int|false
    {
        $this->init();
        return session_gc();
    }

    private function unset()
    {
        if (isset($this->session['_delete'])) {
            if (is_array($this->session['_delete'])) {
                foreach ($this->session['_delete'] as $k) {
                    unset($this->session[$k]);
                }
            } elseif ($this->session['_delete'] === true) {
                $this->session = [];
            }

            unset($this->session['_delete']);
        }
    
        foreach ($this->session as $k => &$v) {
            if (is_array($v)) {
                unset($v);
            }
        }
    }
}
