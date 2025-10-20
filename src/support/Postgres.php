<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\support;

use RuntimeException;

/**
 * PostgresSQL database connection.
 */
final class Postgres extends AbstractPdoConnection
{
    private string $host;
    private string $user;
    private string $password;
    private string $database;
    private int $port;
    /** @var array<int|mixed> */
    private array $options;

    /**
     * @param string $host The hostname or IP address of the PostgresSQL server
     * @param string $user The username for the connection
     * @param string $password The password for the connection
     * @param string $database The name of the PostgresSQL database
     * @param int $port The port of the PostgresSQL server (default: 5432)
     * @param array<int|mixed> $options Additional PDO options
     * @throws RuntimeException On connection error
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 5432,
        array $options = []
    ) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->options = $options;

        $this->connect($this->buildDsn(), $user, $password, $options, $database);
    }

    public function getDriverName(): string
    {
        return 'pgsql';
    }

    protected function buildDsn(): string
    {
        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $this->host,
            $this->port,
            $this->database
        );
    }

    public function reconnect(): void
    {
        $this->disconnect();

        try {
            $this->connect($this->buildDsn(), $this->user, $this->password, $this->options, $this->database);
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                sprintf('PostgresSQL reconnection failed: %s', $e->getMessage()),
                (int)$e->getCode(),
                $e
            );
        }
    }
}
