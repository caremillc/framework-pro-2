<?php declare (strict_types = 1);
namespace Careminate\Databases\Dbal\Connections;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Careminate\Databases\Dbal\Connections\Contracts\ConnectionInterface;

class ConnectionFactory implements ConnectionInterface
{
    public function __construct(private array $config)
    {
    }

    public function create(): Connection
    {
        return DriverManager::getConnection($this->config);
    }
}
