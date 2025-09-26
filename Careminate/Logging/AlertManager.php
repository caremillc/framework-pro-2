<?php declare(strict_types=1);

namespace Careminate\Logging;

use InvalidArgumentException;

class AlertManager
{
    protected static array $levels = [
        'debug'     => 0, 'info' => 1, 'notice' => 2, 'warning' => 3,
        'error' => 4, 'critical' => 5, 'alert' => 6, 'emergency' => 7
    ];

    /** @var AlertChannel[] */
    protected static array $channels = [];

    public static function registerChannel(AlertChannel $channel): void
    {
        self::$channels[] = $channel;
    }

    public static function send(string $level, string $channel, string $message): void
    {
        $alerts = config('log.alerts', []);
        if (empty($alerts['enabled'])) {
            return;
        }

        $level = strtolower($level);
        if (!isset(self::$levels[$level])) {
            throw new InvalidArgumentException("Invalid log level: $level");
        }

        $threshold = strtolower($alerts['threshold_level'] ?? 'error');
        if (self::$levels[$level] < self::$levels[$threshold]) {
            return;
        }

        foreach (self::$channels as $c) {
            $c->send($level, $channel, $message);
        }
    }
}
