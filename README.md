# session
php session library
## Install
composer require alpha-zeta/session
## Usage
config/container.php
```php
return [
    ...
    SessionInterface::class => fn() => new Session(config('session')),
    ...
];
```
then see Az\Session\SessionMiddleware class.

And in anywere:
```php
$this->session->foo = 'bar'
$foo = $this->session->foo;
```

for introduce methods see Az\Session\Session class
