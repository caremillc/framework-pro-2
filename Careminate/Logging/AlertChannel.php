<?php declare(strict_types=1);
namespace Careminate\Logging;

interface AlertChannel
{
    public function send(string $level, string $channel, string $message): void;
}