<?php declare (strict_types = 1);
namespace Careminate\Databases\Dbal\Connections\Contracts;

use Doctrine\DBAL\Connection;

interface ConnectionInterface 
{
    public function create(): Connection;
}