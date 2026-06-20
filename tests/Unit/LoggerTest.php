<?php

namespace App\Tests\Unit;

use App\Logger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private vfsStreamDirectory $root;
    private string $logDir;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('logs');
        $this->logDir = $this->root->url();
        Logger::init($this->logDir);
    }

    public function testLogCreatesFile(): void
    {
        Logger::log('TEST', ['msg' => 'hello']);

        $files = $this->root->getChildren();
        $this->assertCount(1, $files);

        $content = $files[0]->getContent();
        $entry = json_decode($content, true);

        $this->assertSame('TEST', $entry['label']);
        $this->assertSame('info', $entry['level']);
        $this->assertSame(['msg' => 'hello'], $entry['data']);
        $this->assertArrayHasKey('time', $entry);
        $this->assertArrayHasKey('request_id', $entry);
    }

    public function testLogFormatIsJsonl(): void
    {
        Logger::log('A', ['n' => 1]);
        Logger::log('B', ['n' => 2]);

        $lines = file($this->root->getChildren()[0]->url(), FILE_IGNORE_NEW_LINES);

        $this->assertCount(2, $lines);
        $this->assertNotNull(json_decode($lines[0]));
        $this->assertNotNull(json_decode($lines[1]));
    }

    public function testErrorLevel(): void
    {
        Logger::error('ERR', ['msg' => 'fail']);

        $content = $this->root->getChildren()[0]->getContent();
        $entry = json_decode($content, true);

        $this->assertSame('error', $entry['level']);
    }

    public function testDebugLevel(): void
    {
        Logger::debug('DBG', ['msg' => 'debug']);

        $content = $this->root->getChildren()[0]->getContent();
        $entry = json_decode($content, true);

        $this->assertSame('debug', $entry['level']);
    }

    public function testRequestIdIsConsistent(): void
    {
        $id1 = Logger::requestId();
        $id2 = Logger::requestId();

        $this->assertSame($id1, $id2);
        $this->assertStringStartsWith('req_', $id1);
    }

    public function testLogFileNamedByDate(): void
    {
        Logger::log('TEST', []);

        $expectedName = 'bot-' . date('Y-m-d') . '.log';
        $this->assertTrue($this->root->hasChild($expectedName));
    }

    public function testTailReturnsLines(): void
    {
        foreach (range(1, 5) as $i) {
            Logger::log('TAIL', ['i' => $i]);
        }

        $tail = Logger::tail(3);
        $lines = array_filter(explode("\n", $tail));

        $this->assertCount(3, $lines);
    }

    public function testTailReturnsAllWhenFewerLines(): void
    {
        Logger::log('SHORT', ['x' => 1]);

        $tail = Logger::tail(100);
        $lines = array_filter(explode("\n", $tail));

        $this->assertCount(1, $lines);
    }

    public function testTailWhenNoFile(): void
    {
        Logger::init(vfsStream::setup('empty')->url());
        $this->assertStringContainsString('No log file', Logger::tail());
    }

    public function testRecentReturnsEntriesWithinWindow(): void
    {
        Logger::log('OLD', ['ts' => 'old']);
        $recent = Logger::recent(86400);
        $this->assertCount(1, $recent);
    }

    public function testRecentReturnsOnlyRequestedLabel(): void
    {
        Logger::log('A', ['n' => 1]);
        Logger::log('B', ['n' => 2]);

        $entries = Logger::recent(86400);
        $labels = array_column($entries, 'label');

        $this->assertContains('A', $labels);
        $this->assertContains('B', $labels);
    }

    public function testSanitizeDoesNotThrowOnInvalidUtf8(): void
    {
        Logger::log('ENCODING', ['text' => "\x80\x81\x82"]);
        $content = $this->root->getChildren()[0]->getContent();
        $entry = json_decode($content, true);
        $this->assertArrayHasKey('label', $entry);
    }

    public function testLogWithEmptyData(): void
    {
        Logger::log('EMPTY', []);
        $content = $this->root->getChildren()[0]->getContent();
        $entry = json_decode($content, true);
        $this->assertSame([], $entry['data']);
    }

    public function testReinitDoesNotCrash(): void
    {
        Logger::init($this->logDir);
        Logger::log('REINIT', []);
        $files = $this->root->getChildren();
        $this->assertCount(1, $files);
    }

    public function testConcurrentWrites(): void
    {
        $writers = [];
        for ($i = 0; $i < 10; $i++) {
            $writers[] = fn() => Logger::log('CONCURRENT', ['i' => $i]);
        }
        foreach ($writers as $write) {
            $write();
        }
        $lines = file($this->root->getChildren()[0]->url(), FILE_IGNORE_NEW_LINES);
        $this->assertCount(10, $lines);
    }
}
