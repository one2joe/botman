<?php

require __DIR__ . '/../vendor/autoload.php';

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\BotMan;
use App\Conversations\OnboardingConversation;
use App\Logger;
use App\LogMiddleware;
use Dotenv\Dotenv;
use BotMan\Drivers\Line\LineDriver;
use BotMan\Drivers\Line\LineImageDriver;
use BotMan\Drivers\Line\LineLocationDriver;
use BotMan\Drivers\Line\LineStickerDriver;
use Symfony\Component\HttpFoundation\Request;

define('BOTMAN_LOG_DIR', __DIR__ . '/../storage/logs');
Logger::init(BOTMAN_LOG_DIR);

// --- Log viewer endpoints ---
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if ($uri === '/logs' || $uri === '/logs/') {
    header('Content-Type: text/plain; charset=utf-8');
    echo Logger::tail(100);
    return;
}
if ($uri === '/logs.json' || $uri === '/logs.json/') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(Logger::recent(86400), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return;
}

$root = dirname(__DIR__);

if (file_exists($root . '/.env')) {
    $dotenv = Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

$request = Request::createFromGlobals();

Logger::log('REQUEST_RECEIVED', [
    'method' => $request->getMethod(),
    'uri' => $request->getRequestUri(),
    'headers' => [
        'content-type' => $request->headers->get('Content-Type'),
        'x-line-signature' => $request->headers->get('X-Line-Signature') ? substr($request->headers->get('X-Line-Signature'), 0, 20) . '...' : '(none)',
        'user-agent' => $request->headers->get('User-Agent'),
    ],
    'body_raw' => $request->getContent(),
]);

// Check LINE signature manually for debugging
$channelSecret = $_ENV['LINE_CHANNEL_SECRET'] ?? getenv('LINE_CHANNEL_SECRET') ?: '';
$expectedSig = empty($channelSecret) ? '(no secret set)' : base64_encode(hash_hmac('sha256', $request->getContent(), $channelSecret, true));
$actualSig = $request->headers->get('X-Line-Signature', '');

Logger::log('SIGNATURE_CHECK', [
    'channel_secret_set' => !empty($channelSecret),
    'signature_valid' => hash_equals($expectedSig, $actualSig),
    'expected_prefix' => substr($expectedSig, 0, 20) . '...',
    'actual_prefix' => $actualSig ? substr($actualSig, 0, 20) . '...' : '(none)',
]);

foreach ([LineDriver::class, LineImageDriver::class, LineLocationDriver::class, LineStickerDriver::class] as $driverClass) {
    if (class_exists($driverClass)) {
        DriverManager::loadDriver($driverClass);
    }
}

$config = [
    'web' => ['matchingData' => []],
    'line' => [
        'channel_access_token' => $_ENV['LINE_CHANNEL_ACCESS_TOKEN'] ?? getenv('LINE_CHANNEL_ACCESS_TOKEN') ?: '',
        'channel_secret' => $channelSecret,
    ],
];

$botman = BotManFactory::create($config);

$logMiddleware = new LogMiddleware();
$botman->middleware->received($logMiddleware);
$botman->middleware->matching($logMiddleware);
$botman->middleware->heard($logMiddleware);
$botman->middleware->sending($logMiddleware);
$botman->middleware->captured($logMiddleware);

$botman->hears('hi', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'hi', 'handler' => 'reply สวัสดีครับ']);
    $bot->reply('สวัสดีครับ');
});

$botman->hears('onboard', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'onboard']);
    $bot->startConversation(new OnboardingConversation());
});

$botman->hears('onboarding', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'onboarding']);
    $bot->startConversation(new OnboardingConversation());
});

$botman->hears('help', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'help']);
    $bot->reply("พิมพ์ hi เพื่อทดสอบ, onboard เพื่อเริ่ม onboarding, cancel เพื่อยกเลิก, หรือส่งรูป/โลเคชัน/สติกเกอร์มาได้");
});

$botman->fallback(function ($bot) {
    Logger::log('FALLBACK', ['text' => $bot->getMessage()->getText()]);
    $bot->reply('รับข้อความแล้ว: ' . $bot->getMessage()->getText());
});

try {
    $botman->listen();
    Logger::log('LISTEN_COMPLETE', []);
} catch (\Throwable $e) {
    Logger::error('LISTEN_ERROR', [
        'message' => $e->getMessage(),
        'file' => $e->getFile() . ':' . $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString()),
    ]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
