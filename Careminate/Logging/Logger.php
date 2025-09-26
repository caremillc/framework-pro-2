<?php declare(strict_types=1);

namespace Careminate\Logging;

use DateTime;

class Logger
{
    protected string $channel;
    protected string $logPath;
    protected string $driver;
    protected int $maxFileSize;
    protected int $retentionDays;
    protected string $level;

    protected array $levels = [
        'debug'     => 0,
        'info'      => 1,
        'notice'    => 2,
        'warning'   => 3,
        'error'     => 4,
        'critical'  => 5,
        'alert'     => 6,
        'emergency' => 7,
    ];

    public function __construct(array $config)
    {
        $this->channel = $config['channel'] ?? 'default';
        $this->driver  = $config['driver'] ?? 'single';
        $this->logPath = $config['path'] ?? BASE_PATH . "/storage/logs/{$this->channel}.log";
        $this->level   = strtolower($config['level'] ?? 'debug');
        $this->maxFileSize = $config['max_file_size'] ?? 5 * 1024 * 1024;
        $this->retentionDays = $config['retention_days'] ?? 30;

        if (in_array($this->driver, ['single', 'daily']) && !is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0777, true);
        }

        if ($this->driver === 'daily') $this->rotateLog();
        if (in_array($this->driver, ['single', 'daily'])) $this->cleanupOldLogs();
    }

    public function __call($method, $args)
    {
        if (isset($this->levels[$method])) {
            $this->writeLog($method, $args[0] ?? '', $args[1] ?? []);
        }
    }

    protected function writeLog(string $level, string $message, array $context = []): void
    {
        if ($this->levels[$level] < $this->levels[$this->level]) return;

        $timestamp = (new DateTime())->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context, JSON_PRETTY_PRINT) : '';
        $line = sprintf("[%s] [%s] %s %s\n", $timestamp, strtoupper($level), $message, $contextStr);

        match($this->driver) {
            'single', 'daily' => file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX),
            'errorlog'        => error_log(trim($line)),
            'syslog'          => syslog($this->mapLevelToSyslog($level), trim($line)),
            default           => file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX)
        };

        AlertManager::send($level, $this->channel, $line);
    }

    protected function mapLevelToSyslog(string $level): int
    {
        return match($level) {
            'debug'     => LOG_DEBUG,
            'info'      => LOG_INFO,
            'notice'    => LOG_NOTICE,
            'warning'   => LOG_WARNING,
            'error'     => LOG_ERR,
            'critical'  => LOG_CRIT,
            'alert'     => LOG_ALERT,
            'emergency' => LOG_EMERG,
            default     => LOG_INFO
        };
    }

    protected function rotateLog(): void
    {
        $date = (new DateTime())->format('Y-m-d');
        $dailyLog = dirname($this->logPath) . '/' . $this->channel . "-{$date}.log";

        if (!file_exists($dailyLog) && file_exists($this->logPath)) rename($this->logPath, $dailyLog);

        $this->logPath = $dailyLog;

        if (file_exists($this->logPath) && filesize($this->logPath) >= $this->maxFileSize) {
            $i = 1;
            do { $rotated = $this->logPath . '.' . $i++; } while(file_exists($rotated));
            rename($this->logPath, $rotated);
        }
    }

    protected function cleanupOldLogs(): void
    {
        $dir = dirname($this->logPath);
        $files = glob($dir . '/' . $this->channel . '-*.log*');
        $maxAge = $this->retentionDays * 86400;
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) @unlink($file);
        }
    }
}
