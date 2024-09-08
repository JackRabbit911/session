# session
php session library
## Install
composer require alpha-zeta/session
## Usage
config/container.php
```php
return [
    QueryBuilderHandler::class => fn() => (new Connection('mysql', config('database', 'connect.mysql')))->getQueryBuilder(),

    SessionInterface::class => function (QueryBuilderHandler $qb) {
        $handler = match (env('SESSION_DRIVER')) {
            'DB' => new Driver\Db($qb->pdo()),
            default => null,
        };

        return new Session(config('session'), $handler);
    },
];
```
then see Az\Session\SessionMiddleware class.

And in anywere:
```php
$this->session->foo = 'bar'
$foo = $this->session->foo;
```

for introduce methods see Az\Session\Session class
