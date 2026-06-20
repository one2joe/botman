<?php

require __DIR__ . '/../vendor/autoload.php';

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use Dotenv\Dotenv;
use BotMan\Drivers\Line\LineDriver;
use BotMan\Drivers\Line\LineImageDriver;
use BotMan\Drivers\Line\LineLocationDriver;
use BotMan\Drivers\Line\LineStickerDriver;

$root = dirname(__DIR__);

if (file_exists($root . '/.env')) {
    $dotenv = Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

foreach ([LineDriver::class, LineImageDriver::class, LineLocationDriver::class, LineStickerDriver::class] as $driverClass) {
    if (class_exists($driverClass)) {
        DriverManager::loadDriver($driverClass);
    }
}

$config = [
    'web' => ['matchingData' => []],
    'line' => [
        'channel_access_token' => $_ENV['LINE_CHANNEL_ACCESS_TOKEN'] ?? getenv('LINE_CHANNEL_ACCESS_TOKEN') ?: '',
        'channel_secret' => $_ENV['LINE_CHANNEL_SECRET'] ?? getenv('LINE_CHANNEL_SECRET') ?: '',
    ],
];

$botman = BotManFactory::create($config);

$botman->hears('hi', function ($bot) {
    $bot->reply('สวัสดีครับ');
});

$botman->hears('help', function ($bot) {
    $bot->reply("พิมพ์ hi เพื่อทดสอบ, หรือส่งรูป/โลเคชัน/สติกเกอร์มาได้");
});

$botman->fallback(function ($bot) {
    $bot->reply('รับข้อความแล้ว: ' . $bot->getMessage()->getText());
});

$botman->listen();
