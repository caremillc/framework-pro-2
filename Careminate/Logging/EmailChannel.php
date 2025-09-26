<?php declare(strict_types=1);

namespace Careminate\Logging;

class EmailChannel implements AlertChannel
{
    public function send(string $level, string $channel, string $message): void
    {
        $alerts = config('log.alerts.email', []);
        if (empty($alerts['enabled']) || empty($alerts['recipients'])) {
            return;
        }

        $subject = ($alerts['subject_prefix'] ?? '[ALERT]') . " [$level][$channel]";

        foreach ($alerts['recipients'] as $to) {
            if (!mail($to, $subject, $message)) {
                error_log("Failed to send alert email to {$to}");
            }
        }
    }
}
