<?php

namespace App\Tests\Feature;

use App\Conversations\OnboardingConversation;
use App\Logger;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\BotMan\Interfaces\CacheInterface;
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

    protected function createBot(?CacheInterface $cache = null): BotMan
    {
        $botman = BotManFactory::create([], $cache);
        $this->registerHandlers($botman);
        return $botman;
    }

    protected function registerHandlers(BotMan $botman): void
    {
        $botman->hears('สวัสดี', function ($bot) {
            $question = Question::create('สวัสดีครับ มีอะไรให้ช่วย?')
                ->addButton(Button::create('แนะนำตัว')->value('แนะนำตัว'))
                ->addButton(Button::create('คำสั่ง')->value('ช่วยเหลือ'))
                ->addButton(Button::create('ทักทาย')->value('สวัสดี'));
            $bot->reply($question);
        });
        $botman->hears('แนะนำตัว', function ($bot) {
            $bot->startConversation(new OnboardingConversation());
        });
        $botman->hears('สอนการใช้งาน', function ($bot) {
            $bot->startConversation(new OnboardingConversation());
        });
        $botman->hears('\[Image\]', function ($bot) {
            $bot->reply('รับรูปภาพแล้ว');
        });

        $botman->hears('ช่วยเหลือ', function ($bot) {
            $bot->reply("พิมพ์ สวัสดี เพื่อทดสอบ, แนะนำตัว เพื่อเริ่มสอนการใช้งาน, ยกเลิก เพื่อยกเลิก, หรือส่งรูปภาพ/ตำแหน่งที่ตั้ง/สติกเกอร์มาได้");
        });
        $botman->fallback(function ($bot) {
            $bot->reply('รับข้อความแล้ว: ' . $bot->getMessage()->getText());
        });
    }

    public function testHiRepliesWithQuickReplyButtons(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('สวัสดี', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(\BotMan\BotMan\Messages\Outgoing\Question::class, $messages[0]);

        $question = $messages[0];
        $this->assertSame('สวัสดีครับ มีอะไรให้ช่วย?', $question->getText());

        $buttons = $question->getButtons();
        $this->assertCount(3, $buttons);

        $labels = array_column($buttons, 'text');
        $this->assertContains('แนะนำตัว', $labels);
        $this->assertContains('คำสั่ง', $labels);
        $this->assertContains('ทักทาย', $labels);

        $values = array_column($buttons, 'value');
        $this->assertContains('แนะนำตัว', $values);
        $this->assertContains('ช่วยเหลือ', $values);
        $this->assertContains('สวัสดี', $values);
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

    public function testImageHandlerReplies(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('[Image]', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('รับรูปภาพแล้ว', $messages[0]->getText());
    }

    public function testHiInThai(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('สวัสดี', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(Question::class, $messages[0]);
        $this->assertSame('สวัสดีครับ มีอะไรให้ช่วย?', $messages[0]->getText());

        $labels = array_column($messages[0]->getButtons(), 'text');
        $this->assertContains('แนะนำตัว', $labels);
        $this->assertContains('คำสั่ง', $labels);
        $this->assertContains('ทักทาย', $labels);
    }

    public function testOnboardInThai(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('สอนการใช้งาน', $messages[0]->getText());
    }

    public function testOnboardingInThai(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('สอนการใช้งาน', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('สอนการใช้งาน', $messages[0]->getText());
    }

    public function testHelpInThai(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('ช่วยเหลือ', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('สวัสดี', $messages[0]->getText());
        $this->assertStringContainsString('แนะนำตัว', $messages[0]->getText());
    }

    public function testConversationCancelInThai(): void
    {
        $cache = new ArrayCache();

        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();

        $this->fakeDriver->messages = [new IncomingMessage('ยกเลิก', 'Utest', 'token')];
        $this->createBot($cache)->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertStringContainsString('สอนการใช้งาน', $messages[0]->getText());
        $this->assertStringContainsString('ยกเลิกการสอนการใช้งานแล้ว', $messages[1]->getText());
        foreach ($messages as $msg) {
            $this->assertStringNotContainsString('สรุปการสอนการใช้งาน', $msg->getText());
        }
    }

    public function testCancelWithoutConversationFallsBack(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('ยกเลิก', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('รับข้อความแล้ว', $messages[0]->getText());
        $this->assertStringContainsString('ยกเลิก', $messages[0]->getText());
    }

    public function testHelpReplies(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('ช่วยเหลือ', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('สวัสดี', $messages[0]->getText());
        $this->assertStringContainsString('แนะนำตัว', $messages[0]->getText());
    }

    public function testOnboardStartsConversation(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('สอนการใช้งาน', $messages[0]->getText());
    }

    public function testOnboardingStartsConversation(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('สอนการใช้งาน', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('สอนการใช้งาน', $messages[0]->getText());
    }

    public function testConversationAsksForName(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('สอนการใช้งาน', 'Utest', 'token')];

        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('สอนการใช้งาน', $messages[0]->getText());
        $this->assertStringContainsString('ชื่อ', $messages[0]->getText());
    }

    public function testConversationNotResumedAcrossInstancesWithIsolatedCache(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot()->listen();

        $this->fakeDriver->messages = [new IncomingMessage('ตอบชื่อ', 'Utest', 'token')];
        $this->createBot()->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString('ชื่อ', $messages[0]->getText());
        $this->assertStringContainsString('รับข้อความแล้ว', $messages[1]->getText());
    }

    public function testConversationResumedAcrossInstancesWithSharedCache(): void
    {
        $cache = new ArrayCache();

        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();

        $this->fakeDriver->messages = [new IncomingMessage('สมชาย', 'Utest', 'token')];
        $this->createBot($cache)->listen();

        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertStringContainsString('ชื่อ', $messages[0]->getText());
        $this->assertStringContainsString('บอท', $messages[1]->getText());
    }
}
