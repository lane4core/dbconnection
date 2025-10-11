# DbConnection

Ein flexibles Paket zur Erzeugung und Verwaltung von Datenbankverbindungen (PDO-basiert) mit Unterstützung für **MySQL/MariaDB**, **PostgreSQL** und **SQLite**. 

---

## Überblick

Das Paket stellt eine zentrale Factory bereit, um PDO-basierte Verbindungen einheitlich und konsistent zu erzeugen. Dabei sind spezifische Verbindungen für gängige DB-Treiber (MySQL/MariaDB, PostgreSQL, SQLite) integriert.

Der Zweck:

* Einheitliches Management von Verbindungsparametern
* Standardisierung von PDO-Optionen
* Möglichkeit, das Verhalten durch eigene Wrapper zu erweitern
* Gute Testbarkeit und Austauschbarkeit

---

## Unterstützte Verbindungsarten

Das Paket enthält Implementierungen für folgende Connection-Typen:

* **MySQL / MariaDB** → `MySql`
* **PostgreSQL** → `Postgres`
* **SQLite** → `Sqlite`

Jede Connection-Klasse kümmert sich um den passenden DSN-Aufbau und initialisiert automatisch die jeweilige PDO-Verbindung mit sinnvollen Standardwerten.

---

## Installation

Mit Composer:

```bash
composer require lane4core/dbconnection
```

Beim Entwickeln lokal:

```bash
git clone https://github.com/lane4core/dbconnection.git
cd dbconnection
composer install
```

---

## Konfiguration

Alle Connection-Klassen erwarten ein Konfigurationsarray mit Parametern wie:

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

Für SQLite:

```php
[
    'driver'   => 'sqlite',
    'database' => ':memory:', // oder Pfad zur Datei
]
```

---

## Beispiele

### MySQL / MariaDB

```php
use Lane4Core\DbConnection\Connection\MySqlConnection;

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
use Lane4Core\DbConnection\Connection\PostgreSqlConnection;

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

### SQLite (In‑Memory)

```php
use Lane4Core\DbConnection\Connection\SqliteConnection;

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

## Erweiterung / Custom Wrappers

Wenn du spezielles Verhalten wie Logging, Query-Profiling oder Caching implementieren möchtest:

1. Erstelle eine eigene Wrapper-Klasse, die PDO kapselt oder erweitert.
2. Nutze diese Wrapper in deiner eigenen Connection-Klasse.
3. Ergänze Hooks oder Events, um vor/nach Queries zusätzliche Aktionen auszuführen.

---

## Tests

Das Projekt enthält PHPUnit-Tests unter `tests/`.

```bash
vendor/bin/phpunit
```

Standardmäßig wird SQLite für Tests verwendet.

---

## CI & Codequalität

Im Repository werden folgende Tools empfohlen oder genutzt:

* **PHPStan** für statische Analyse (Level 8)
* **PHPCS** für Code-Stilprüfungen
* **PHPUnit** für Unit-Tests (100% code coverage)
* GitHub Actions für CI/CD

Typische CI-Schritte:

1. `composer install`
2. `make phpcs`
3. `make phpstan`
4. `make phpunit-coverage`

---

## Roadmap & Hinweise
* Connection-Pooling (Kontext: Application Server) -> DbConnectionPool (10.2025)
* Datenbank Schema -> DbSchema (10.2025)
* Datenbank Query -> DbQuery (10.2025)
* PSR‑kompatible Logger-Integration (11.2025)

---

## Lizenz

Dieses Projekt steht unter der **MIT License** – siehe `LICENSE` im Repository.
---

Viel Spass und Erfolg mit **DbConnection**!
