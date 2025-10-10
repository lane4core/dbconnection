<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\Tests\integration;

use Lane4core\DbConnection\DbConnection;
use Lane4core\DbConnection\support\MySql;
use Lane4core\DbConnection\support\Postgres;
use Lane4core\DbConnection\support\SqLite;
use Lane4core\Contract\Database\DbConnectionFactoryInterface;
use Lane4core\Contract\Database\DbConnectionInterface;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * Integration tests for DbConnection factory.
 */
final class DbConnectionTest extends TestCase
{
    private DbConnection $factory;
    private ?DbConnectionInterface $connection = null;
    private ?string $sqliteDbPath = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new DbConnection();
        $this->sqliteDbPath = sys_get_temp_dir() . '/test_factory_' . uniqid() . '.db';
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            $this->connection->disconnect();
            $this->connection = null;
        }

        if ($this->sqliteDbPath !== null && file_exists($this->sqliteDbPath)) {
            unlink($this->sqliteDbPath);
        }

        parent::tearDown();
    }

    public function testGetSupportedDrivers(): void
    {
        $drivers = $this->factory->getSupportedDrivers();

        $this->assertIsArray($drivers);
        $this->assertCount(3, $drivers);
        $this->assertContains(DbConnectionFactoryInterface::DRIVER_MYSQL, $drivers);
        $this->assertContains(DbConnectionFactoryInterface::DRIVER_PGSQL, $drivers);
        $this->assertContains(DbConnectionFactoryInterface::DRIVER_SQLITE, $drivers);
    }

    public function testSupportsDriverReturnsTrueForSupportedDrivers(): void
    {
        $this->assertTrue($this->factory->supportsDriver(DbConnectionFactoryInterface::DRIVER_MYSQL));
        $this->assertTrue($this->factory->supportsDriver(DbConnectionFactoryInterface::DRIVER_PGSQL));
        $this->assertTrue($this->factory->supportsDriver(DbConnectionFactoryInterface::DRIVER_SQLITE));
    }

    public function testSupportsDriverReturnsFalseForUnsupportedDrivers(): void
    {
        $this->assertFalse($this->factory->supportsDriver('oracle'));
        $this->assertFalse($this->factory->supportsDriver('mssql'));
        $this->assertFalse($this->factory->supportsDriver('unknown'));
    }

    public function testCreateSqliteConnection(): void
    {
        $config = [
            'path' => $this->sqliteDbPath,
        ];

        $this->connection = $this->factory->create(DbConnectionFactoryInterface::DRIVER_SQLITE, $config);

        $this->assertInstanceOf(SqLite::class, $this->connection);
        $this->assertInstanceOf(DbConnectionInterface::class, $this->connection);
        $this->assertTrue($this->connection->isConnected());
        $this->assertSame('sqlite', $this->connection->getDriverName());
    }

    public function testCreateSqliteConnectionWithOptions(): void
    {
        $config = [
            'path' => $this->sqliteDbPath,
            'options' => [
                \PDO::ATTR_TIMEOUT => 5,
            ],
        ];

        $this->connection = $this->factory->create(DbConnectionFactoryInterface::DRIVER_SQLITE, $config);

        $this->assertInstanceOf(SqLite::class, $this->connection);
        $this->assertTrue($this->connection->isConnected());
    }

    public function testCreateMySqlConnection(): void
    {
        if (!$this->isMySqlAvailable()) {
            $this->markTestSkipped('MySQL server is not available');
        }

        $config = [
            'host' => getenv('MYSQL_HOST') ?: 'mysql',
            'port' => (int)(getenv('MYSQL_PORT') ?: 3306),
            'database' => getenv('MYSQL_DATABASE') ?: 'test_db',
            'user' => getenv('MYSQL_USER') ?: 'test_user',
            'password' => getenv('MYSQL_PASSWORD') ?: 'test_password',
        ];

        $this->connection = $this->factory->create(DbConnectionFactoryInterface::DRIVER_MYSQL, $config);

        $this->assertInstanceOf(MySql::class, $this->connection);
        $this->assertInstanceOf(DbConnectionInterface::class, $this->connection);
        $this->assertTrue($this->connection->isConnected());
        $this->assertSame('mysql', $this->connection->getDriverName());
    }

    public function testCreatePostgresConnection(): void
    {
        if (!$this->isPostgresAvailable()) {
            $this->markTestSkipped('PostgreSQL server is not available');
        }

        $config = [
            'host' => getenv('POSTGRES_HOST') ?: 'postgres',
            'port' => (int)(getenv('POSTGRES_PORT') ?: 5499),
            'database' => getenv('POSTGRES_DATABASE') ?: 'test_db',
            'user' => getenv('POSTGRES_USER') ?: 'test_user',
            'password' => getenv('POSTGRES_PASSWORD') ?: 'test_password',
        ];

        $this->connection = $this->factory->create(DbConnectionFactoryInterface::DRIVER_PGSQL, $config);

        $this->assertInstanceOf(Postgres::class, $this->connection);
        $this->assertInstanceOf(DbConnectionInterface::class, $this->connection);
        $this->assertTrue($this->connection->isConnected());
        $this->assertSame('pgsql', $this->connection->getDriverName());
    }

    public function testCreateThrowsExceptionForUnsupportedDriver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver: oracle');

        $this->factory->create('oracle', []);
    }

    public function testCreateSqliteThrowsExceptionWhenPathMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing path');

        $this->factory->create(DbConnectionFactoryInterface::DRIVER_SQLITE, []);
    }

    public function testCreateMySqlThrowsExceptionWhenHostMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing host');

        $this->factory->create(DbConnectionFactoryInterface::DRIVER_MYSQL, [
            'user' => 'test',
            'password' => 'test',
            'database' => 'test',
        ]);
    }

    public function testCreateMySqlThrowsExceptionWhenUserMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing user');

        $this->factory->create(DbConnectionFactoryInterface::DRIVER_MYSQL, [
            'host' => 'localhost',
            'password' => 'test',
            'database' => 'test',
        ]);
    }

    public function testCreateMySqlThrowsExceptionWhenPasswordMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing password');

        $this->factory->create(DbConnectionFactoryInterface::DRIVER_MYSQL, [
            'host' => 'localhost',
            'user' => 'test',
            'database' => 'test',
        ]);
    }

    public function testCreateMySqlThrowsExceptionWhenDatabaseMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing database');

        $this->factory->create(DbConnectionFactoryInterface::DRIVER_MYSQL, [
            'host' => 'localhost',
            'user' => 'test',
            'password' => 'test',
        ]);
    }

    private function isMySqlAvailable(): bool
    {
        try {
            $config = [
                'host' => getenv('MYSQL_HOST') ?: 'mysql',
                'port' => (int)(getenv('MYSQL_PORT') ?: 3306),
                'database' => getenv('MYSQL_DATABASE') ?: 'test_db',
                'user' => getenv('MYSQL_USER') ?: 'test_user',
                'password' => getenv('MYSQL_PASSWORD') ?: 'test_password',
            ];
            $connection = $this->factory->create(DbConnectionFactoryInterface::DRIVER_MYSQL, $config);
            $available = $connection->isConnected();
            $connection->disconnect();
            return $available;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isPostgresAvailable(): bool
    {
        try {
            $config = [
                'host' => getenv('POSTGRES_HOST') ?: 'postgres',
                'port' => (int)(getenv('POSTGRES_PORT') ?: 5499),
                'database' => getenv('POSTGRES_DATABASE') ?: 'test_db',
                'user' => getenv('POSTGRES_USER') ?: 'test_user',
                'password' => getenv('POSTGRES_PASSWORD') ?: 'test_password',
            ];
            $connection = $this->factory->create(DbConnectionFactoryInterface::DRIVER_PGSQL, $config);
            $available = $connection->isConnected();
            $connection->disconnect();
            return $available;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function testCreateMySqlConnectionWithInvalidPortThrowsException(): void
    {
        $config = [
            'host' => getenv('MYSQL_HOST') ?: 'mysql',
            'port' => 9999, // Ungültiger Port
            'database' => getenv('MYSQL_DATABASE') ?: 'test_db',
            'user' => getenv('MYSQL_USER') ?: 'test_user',
            'password' => getenv('MYSQL_PASSWORD') ?: 'test_password',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to mysql database');

        $this->factory->create(DbConnectionFactoryInterface::DRIVER_MYSQL, $config);
    }

    public function testCreatePostgresConnectionWithInvalidPortThrowsException(): void
    {
        $config = [
            'host' => getenv('POSTGRES_HOST') ?: 'postgres',
            'port' => 9999, // Ungültiger Port
            'database' => getenv('POSTGRES_DATABASE') ?: 'test_db',
            'user' => getenv('POSTGRES_USER') ?: 'test_user',
            'password' => getenv('POSTGRES_PASSWORD') ?: 'test_password',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to pgsql database');

        $this->factory->create(DbConnectionFactoryInterface::DRIVER_PGSQL, $config);
    }
}
