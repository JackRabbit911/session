<?php

namespace Az\Session;

interface SessionInterface
{
    /**
     * Get or set session id
     */
    public function id(?string $id = null): string;

    /**
     * Set session section.
     * @param string $name. A name of section.
     * @return self clone of Session instance.
     */
    // public function section(string $name): self;

    /**
     * Start the session if not yet. And:
     * 1. Set link to the section key of $_SESSION
     * 2. Set user_agent and ip_addr to session for Guard
     * 3. Rename key '_flash' to '_delete' for short keys
     * 4. Set created time for regenerate, if it's necessary
     * @param string section name
     * @return void
     */
    public function init(): void;

    /**
     * magic method that implements set method
     */
    public function __set(string $key, $value): void;

    /**
     * magic method that implements get method
     */
    public function __get(string $key): mixed;

    /**
     * Refresh created time and regenerate id
     * @param bool
     * @return true|false
     */
    public function regenerate(bool $delete_old_session = false): bool;

    /**
     * Returns true if key exists in section, or false if not
     * @param string key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Returns value of the key
     * @param string|null key
     * @param mixed default value
     * @return mixed
     */
    public function get(?string $key = null, $default = null): mixed;

    /**
     * Returns value of the key and mark this key to delete
     * @param string|null key
     * @param mixed default value
     * @return mixed
     */
    public function pull(?string $key = null, $default = null): mixed;

    /**
     * @param string key where to write the counter
     * @param int step
     * @return int
     */
    public function increment(string $key, int $step = 1): int;

     /**
     * @param string key where to write the counter
     * @param int step
     * @return int
     */
    public function decrement(string $key, int $step = 1): int;

    /**
     * Set key/value pair
     * @param string key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value = null): void;

    /**
     * Set key/value pair for current and next request
     * @param string key
     * @param mixed $value
     * @return void
     */
    public function flash(string $key, $value): void;

    /**
     * Keep this item in session untill next request and return this item
     * @param string key
     * @return mixed
     */
    public function keep(string $key): mixed;

    /**
     * Mark to delete keys from arguments
     * Example: delete() or delete([null]) - mark all
     * delete($key) - mark this key
     * delete(key1, key2, ...) - mark this keys
     * @param mixed keys
     * @return void
     */
    public function delete(): void;

    /**
     * Unset marked keys and store session data
     * @return true|false
     */
    public function commit(): bool;

    /**
     * Remove sessipon cookie, if it's necessary
     * and destroy session store
     */
    public function destroy(bool $killCookie = true): void;

    /**
     * cleans up debris
     * @return int|false number of records of false
     */
    public function gc(): int|false;
}
