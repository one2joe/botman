<?php

namespace App\Tests\Feature;

use App\Conversations\OnboardingConversation;
use App\Logger;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class BotResponseTest extends TestCase
{
    private FakeDriver $fakeDriver;

    public static function setUpBeforeClass(): void
    {
        DriverManager::loadDriver(ProxyDriver::class);
    }

    public static function tearDownAfterClass(): void
    {
        DriverManager::unloadDriver(ProxyDriver::class);
    }

    protected function setUp(): void
    {
        Logger::init(vfsStream::setup('logs')->url());
        $this->fakeDriver = new FakeDriver();
        ProxyDriver::setInstance($this->fakeDriver);
    }

    protected function createBot(): BotMan
    {
        $botman = BotManFactory::create([]);
        $botman->hears('hi', function ($bot) {
            $bot->reply('สวัสดีครับ');
        });
        $botman->hears('onboard', function ($bot) {
            $bot->startConversation(new OnboardingConversation());
        });
        $botman->hears('onboarding', function ($bot) {
            $bot->startConversation(new OnboardingConversation());
        });
        $botman->hears('help', function ($bot) {
            $bot->reply("พิมพ์ hi เพื่อทดสอบ, onboard เพื่อเริ่ม onboarding, cancel เพื่อยกเลิก, หรือส่งรูป/โลเคชัน/สติกเกอร์มาได้");
        });
        $botman->fallback(function ($bot) {
            $bot->reply('รับข้อความแล้ว: ' . $bot->getMessage()->getText());
        });
        return $botman;
    }

    public function testHiRepliesSawasdee(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('hi', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('สวัสดีครับ', $messages[0]->getText());
    }

    public function testUnknownMessageFallsBack(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('xyz', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('รับข้อความแล้ว', $messages[0]->getText());
        $this->assertStringContainsString('xyz', $messages[0]->getText());
    }

    public function testHelpReplies(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('help', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('hi', $messages[0]->getText());
        $this->assertStringContainsString('onboard', $messages[0]->getText());
    }

    public function testOnboardStartsConversation(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('onboard', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('onboarding', $messages[0]->getText());
    }

    public function testOnboardingStartsConversation(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('onboarding', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('onboarding', $messages[0]->getText());
    }

    public function testConversationCancelStops(): void
    {
        $this->fakeDriver->messages = [
            new IncomingMessage('onboard', 'Utest', 'token'),
            new IncomingMessage('cancel', 'Utest', 'token'),
        ];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
    }

    public function testConversationAsksForName(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('onboarding', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertStringContainsString('onboarding', $messages[0]->getText());
        $this->assertStringContainsString('ชื่อ', $messages[1]->getText());
    }
}
