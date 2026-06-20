<?php

namespace App;

class Logger
{
    private static string $logDir;
    private static ?string $requestId = null;

    public static function init(string $logDir): void
    {
        self::$logDir = rtrim($logDir, '/\\');
        self::$requestId = uniqid('req_', true);
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0777, true);
        }
    }

    public static function requestId(): string
    {
        return self::$requestId ??= uniqid('req_', true);
    }

    public static function log(string $label, $data, string $level = 'info'): void
    {
        $entry = [
            'time' => date('Y-m-d\TH:i:s.vP'),
            'request_id' => self::requestId(),
            'level' => $level,
            'label' => $label,
            'data' => self::sanitize($data),
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
        $file = self::$logDir . '/bot-' . date('Y-m-d') . '.log';

        file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX);
    }

    public static function error(string $label, $data): void
    {
        self::log($label, $data, 'error');
    }

    public static function debug(string $label, $data): void
    {
        self::log($label, $data, 'debug');
    }

    private static function sanitize($data)
    {
        if (is_string($data)) {
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            $data = str_replace(["\r\n", "\r"], "\n", $data);
            return $data;
        }
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[self::sanitizeKey($key)] = self::sanitize($value);
            }
            return $result;
        }
        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                return self::sanitize($data->toArray());
            }
            if (method_exists($data, '__toString')) {
                return self::sanitize((string) $data);
            }
            return self::sanitize((array) $data);
        }
        return $data;
    }

    private static function sanitizeKey(string $key): string
    {
        $sensitive = ['access_token', 'channel_secret', 'Authorization', 'password', 'secret', 'token'];
        foreach ($sensitive as $s) {
            if (stripos($key, $s) !== false) {
                return $key . '_masked';
            }
        }
        return $key;
    }

    public static function tail(int $lines = 50): string
    {
        $file = self::$logDir . '/bot-' . date('Y-m-d') . '.log';
        if (!file_exists($file)) {
            return "No log file for today.";
        }
        $data = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $data = array_slice($data, -$lines);
        return implode("\n", $data);
    }

    public static function recent(int $seconds = 300): array
    {
        $file = self::$logDir . '/bot-' . date('Y-m-d') . '.log';
        if (!file_exists($file)) return [];
        $cutoff = time() - $seconds;
        $entries = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $entry = json_decode($line, true);
            if ($entry && isset($entry['time'])) {
                $entryTime = strtotime($entry['time']);
                if ($entryTime >= $cutoff) {
                    $entries[] = $entry;
                }
            }
        }
        return $entries;
    }
}
