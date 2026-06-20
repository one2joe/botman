<?php

namespace App\Tests\Unit;

use App\Logger;
use App\LogMiddleware;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogMiddlewareTest extends TestCase
{
    private LogMiddleware $middleware;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('logs');
        Logger::init($this->root->url());
        $this->middleware = new LogMiddleware();
    }

    public function testReceivedLogsAndPassesThrough(): void
    {
        $message = new IncomingMessage('hi', 'user1', 'token1');
        $next = fn($msg) => $msg;

        $result = $this->middleware->received($message, $next, $this->createBotMock());

        $this->assertSame($message, $result);
        $this->assertLogContains('RECEIVED');
    }

    public function testMatchingLogsAndReturnsRegexResult(): void
    {
        $message = new IncomingMessage('hi', 'user1', 'token1');

        $result = $this->middleware->matching($message, 'hi', true);

        $this->assertTrue($result);
        $this->assertLogContains('MATCHING');
    }

    public function testMatchingReturnsFalseWhenNoMatch(): void
    {
        $message = new IncomingMessage('bye', 'user1', 'token1');

        $result = $this->middleware->matching($message, 'hi', false);

        $this->assertFalse($result);
    }

    public function testHeardLogsAndPassesThrough(): void
    {
        $message = new IncomingMessage('hi', 'user1', 'token1');
        $next = fn($msg) => $msg;

        $result = $this->middleware->heard($message, $next, $this->createBotMock());

        $this->assertSame($message, $result);
        $this->assertLogContains('HEARD');
    }

    public function testSendingLogsAndPassesThrough(): void
    {
        $payload = ['replyToken' => 'abc', 'messages' => []];
        $next = fn($p) => $p;

        $result = $this->middleware->sending($payload, $next, $this->createBotMock());

        $this->assertSame($payload, $result);
        $this->assertLogContains('SENDING');
    }

    public function testCapturedLogsAndPassesThrough(): void
    {
        $message = new IncomingMessage('test', 'user1', 'token1');
        $next = fn($msg) => $msg;

        $result = $this->middleware->captured($message, $next, $this->createBotMock());

        $this->assertSame($message, $result);
        $this->assertLogContains('CAPTURED');
    }

    private function createBotMock(): MockObject
    {
        return $this->createMock(BotMan::class);
    }

    private function assertLogContains(string $label): void
    {
        $found = false;
        foreach ($this->root->getChildren() as $file) {
            if (str_contains($file->getContent(), "\"$label\"")) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "Log does not contain label: $label");
    }
}
