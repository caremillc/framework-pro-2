<?php declare(strict_types=1);

namespace Careminate\Logging;

class AlertManager
{
    protected static array $levels = [
        'debug'     => 0, 'info' => 1, 'notice' => 2, 'warning' => 3,
        'error' => 4, 'critical' => 5, 'alert' => 6, 'emergency' => 7
    ];

    public static function send(string $level, string $channel, string $message): void
    {
        $alerts = config('log.alerts', []);
        if (empty($alerts['enabled'])) return;

        $threshold = $alerts['threshold_level'] ?? 'error';
        if (self::$levels[strtolower($level)] < self::$levels[strtolower($threshold)]) return;

        // Email
        if (!empty($alerts['email']['enabled'])) {
            $subject = ($alerts['email']['subject_prefix'] ?? '[ALERT]') . " [$level][$channel]";
            foreach ($alerts['email']['recipients'] ?? [] as $to) {
                @mail($to, $subject, $message);
            }
        }

        // Slack
        if (!empty($alerts['slack']['enabled']) && !empty($alerts['slack']['webhook_url'])) {
            $payload = json_encode(['text' => "[$level][$channel] $message"]);
            @file_get_contents($alerts['slack']['webhook_url'], false, stream_context_create([
                'http' => ['method' => 'POST','header'=>'Content-Type: application/json','content'=>$payload]
            ]));
        }
    }
}
