<?php

declare(strict_types=1);

namespace Lane4core\DbConnection\support;

use RuntimeException;

/**
 * MySQL database connection.
 */
class MySql extends AbstractPdoConnection
{
    private string $host;
    private string $user;
    private string $password;
    private string $database;
    private int $port;
    private string $charset;
    /** @var array<int|mixed> */
    private array $options;

    /**
     * @param string $host The hostname or IP address of the MySQL server
     * @param string $user The username for the connection
     * @param string $password The password for the connection
     * @param string $database The name of the MySQL database
     * @param int $port The port of the MySQL server (default: 3306)
     * @param string $charset The character set to use (default: utf8mb4)
     * @param array<int|mixed> $options Additional PDO options
     * @throws RuntimeException On connection error
     */
    public function __construct(
        string $host,
        string $user,
        string $password,
        string $database,
        int $port = 3306,
        string $charset = 'utf8mb4',
        array $options = []
    ) {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->charset = $charset;
        $this->options = $options;

        $this->connect($this->buildDsn(), $user, $password, $options, $database);
    }

    public function getDriverName(): string
    {
        return 'mysql';
    }

    protected function buildDsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->database,
            $this->charset
        );
    }

    public function reconnect(): void
    {
        $this->disconnect();

        try {
            $this->connect($this->buildDsn(), $this->user, $this->password, $this->options, $this->database);
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                sprintf('MySQL reconnection failed: %s', $e->getMessage()),
                (int)$e->getCode(),
                $e
            );
        }
    }
}
