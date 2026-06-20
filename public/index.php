<?php

require __DIR__ . '/../vendor/autoload.php';

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\FileCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\BotMan;
use App\Conversations\OnboardingConversation;
use App\Logger;
use App\LogMiddleware;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
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

$cacheDir = __DIR__ . '/../storage/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}
$botman = BotManFactory::create($config, new FileCache($cacheDir));

$logMiddleware = new LogMiddleware();
$botman->middleware->received($logMiddleware);
$botman->middleware->matching($logMiddleware);
$botman->middleware->heard($logMiddleware);
$botman->middleware->sending($logMiddleware);
$botman->middleware->captured($logMiddleware);

$botman->hears('สวัสดี', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'สวัสดี', 'handler' => 'reply with quick reply']);
    $question = Question::create('สวัสดีครับ มีอะไรให้ช่วย?')
        ->addButton(Button::create('แนะนำตัว')->value('แนะนำตัว'))
        ->addButton(Button::create('คำสั่ง')->value('ช่วยเหลือ'))
        ->addButton(Button::create('ทักทาย')->value('สวัสดี'));
    $bot->reply($question);
});

$botman->hears('แนะนำตัว', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'แนะนำตัว']);
    $bot->startConversation(new OnboardingConversation());
});

$botman->hears('สอนการใช้งาน', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'สอนการใช้งาน']);
    $bot->startConversation(new OnboardingConversation());
});

$botman->hears('\[Image\]', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'image']);
    $bot->reply('รับรูปภาพแล้ว');
});

$botman->hears('ช่วยเหลือ', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'ช่วยเหลือ']);
    $bot->reply("พิมพ์ สวัสดี เพื่อทดสอบ, แนะนำตัว เพื่อเริ่มสอนการใช้งาน, ยกเลิก เพื่อยกเลิก, หรือส่งรูปภาพ/ตำแหน่งที่ตั้ง/สติกเกอร์มาได้");
});

$botman->fallback(function ($bot) {
    Logger::log('FALLBACK', ['text' => $bot->getMessage()->getText()]);
    $bot->reply('ไม่เข้าใจครับ พิมพ์ ช่วยเหลือ เพื่อดูว่าผมทำอะไรได้บ้าง');
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
