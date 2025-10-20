# DbConnection

A flexible package for creating and managing database connections (PDO-based) with support for **MySQL/MariaDB**, **PostgresSQL**, and **SQLite**.

---

## Overview

This package provides a central factory to create PDO-based connections in a consistent and standardized way. It includes specific connection implementations for common database drivers (MySQL/MariaDB, PostgreSQL, SQLite).

Purpose:

* Unified management of connection parameters
* Standardized PDO options
* Extensible behavior through custom wrappers
* Improved testability and replaceability

---

## Supported Connection Types

The package includes implementations for the following connection types:

* **MySQL / MariaDB** → `MySql`
* **PostgreSQL** → `Postgres`
* **SQLite** → `Sqlite`

Each connection class handles the appropriate DSN construction and automatically initializes the corresponding PDO connection with sensible default settings.

---

## Installation

Using Composer:

```bash
composer require lane4core/dbconnection
```

For local development:

```bash
git clone https://github.com/lane4core/dbconnection.git
cd dbconnection
make install
```

---

## Configuration

All connection classes expect a configuration array with parameters such as:

```php
[
    'driver'   => 'mysql' | 'pgsql',
    'host'     => 'localhost',
    'port'     => 3306 | 5432,
    'database' => 'mydb',
    'username' => 'user',
    'password' => 'secret',
    'charset'  => 'utf8mb4',
    'options'  => [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ],
]
```

For SQLite:

```php
[
    'driver'   => 'sqlite',
    'database' => ':memory:', // or file path
]
```

---

## Examples

### MySQL / MariaDB

```php
use Lane4Core\DbConnection\Support\MySql;

$config = [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'testdb',
    'username' => 'dbuser',
    'password' => 'dbpass',
    'charset'  => 'utf8mb4',
];

$connection = new MySql($config);

$stmt = $connection->pdo()->query('SELECT * FROM users');
$rows = $stmt->fetchAll();
```

### PostgreSQL

```php
use Lane4Core\DbConnection\Support\Postgres;

$config = [
    'driver'   => 'pgsql',
    'host'     => 'localhost',
    'port'     => 5432,
    'database' => 'testdb',
    'username' => 'pguser',
    'password' => 'pgpass',
];

$connection = new Postgres($config);

$stmt = $connection->pdo()->query('SELECT * FROM accounts');
$data = $stmt->fetchAll();
```

### SQLite (In-Memory)

```php
use Lane4Core\DbConnection\Support\SqLite;

$config = [
    'driver'   => 'sqlite',
    'database' => ':memory:',
];

$connection = new Sqlite($config);
$pdo = $connection->pdo();

$pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
$pdo->exec("INSERT INTO test (name) VALUES ('Alice')");
$result = $pdo->query('SELECT * FROM test')->fetchAll();
```

---

## Extension / Custom Wrappers

If you want to implement custom behaviors such as logging, query profiling, or caching:

1. Create your own wrapper class that encapsulates or extends PDO.
2. Use this wrapper in your custom connection class.
3. Add hooks or events to execute additional actions before/after queries.

---

## Tests

The project includes PHPUnit tests under `tests/`.

```bash
make phpunit
```

---

## CI & Code Quality

The repository uses or recommends the following tools:

* **PHPStan** for static analysis (Level 8)
* **PHPCS** for coding standards
* **PHPUnit** for unit tests (100% code coverage)
* GitHub Actions for CI/CD

Typical CI steps:

1. `composer install`
2. `make phpcs`
3. `make phpstan`
4. `make phpunit-coverage`

---

## License

This project is licensed under the **MIT License** – see `LICENSE` in the repository.

---

Enjoy and good luck with **DbConnection**!
