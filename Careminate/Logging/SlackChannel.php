<?php declare(strict_types=1);

namespace Careminate\Logging;

class SlackChannel implements AlertChannel
{
    public function send(string $level, string $channel, string $message): void
    {
        $alerts = config('log.alerts.slack', []);
        if (empty($alerts['enabled']) || empty($alerts['webhook_url'])) {
            return;
        }

        $payload = json_encode(['text' => "[$level][$channel] $message"]);
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload
            ]
        ]);

        $result = @file_get_contents($alerts['webhook_url'], false, $context);
        if ($result === false) {
            error_log("Slack alert failed for $level [$channel]");
        }
    }
}
