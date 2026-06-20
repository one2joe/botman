# น้องเวบ Personality Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** เปลี่ยนบุคลิก LINE Bot จากสุภาพเป็น "น้องเวบ" casual หญิง + เพิ่ม triggers และ media handlers

**Architecture:** แก้ไขตรงๆ ที่ handler closures ใน `public/index.php` และ `OnboardingConversation.php` โดยไม่เพิ่ม abstraction layer ใหม่ ขยาย cancel triggers ใน `BaseConversation.php`

**Tech Stack:** PHP 8.4, BotMan, LINE Driver, PHPUnit 11

---

### Task 1: BaseConversation — ขยาย cancel triggers + ข้อความใหม่

**Files:**
- Modify: `src/Conversations/BaseConversation.php`
- Test: `tests/Feature/BotResponseTest.php`

- [ ] **Step 1: เขียน test ล่วงหน้าสำหรับ cancel triggers ใหม่**

เพิ่ม 4 method ใน `BotResponseTest`:
```php
    public function testCancelWithCancelWordInConversation(): void
    {
        $cache = new ArrayCache();
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $this->fakeDriver->messages = [new IncomingMessage('cancel', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertStringContainsString('ยกเลิกแล้ว', $messages[1]->getText());
    }

    public function testCancelWithPorWordInConversation(): void
    {
        $cache = new ArrayCache();
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $this->fakeDriver->messages = [new IncomingMessage('พอ', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertStringContainsString('ยกเลิกแล้ว', $messages[1]->getText());
    }

    public function testCancelWithYutWordInConversation(): void
    {
        $cache = new ArrayCache();
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $this->fakeDriver->messages = [new IncomingMessage('หยุด', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertStringContainsString('ยกเลิกแล้ว', $messages[1]->getText());
    }
```

- [ ] **Step 2: รัน test เพื่อยืนยันว่าล้มเหลว**

Run: `composer test -- --filter='testCancelWith(Cancel|Por|Yut)WordInConversation'`
Expected: 3 FAILURES (cancel message not found — current response is "ยกเลิกการสอนการใช้งานแล้ว")

- [ ] **Step 3: แก้ BaseConversation.php**

เปลี่ยนจาก:
```php
if (trim($answer->getText()) === 'ยกเลิก') {
    $this->bot->reply('ยกเลิกการสอนการใช้งานแล้ว');
```
เป็น:
```php
if (in_array(trim($answer->getText()), ['ยกเลิก', 'cancel', 'พอ', 'หยุด'])) {
    $this->bot->reply('ยกเลิกแล้วนะคะ~ ✅ ไม่เป็นไรเลยค่าาา อยากทำอะไรต่อบอกน้องได้เลย');
```

- [ ] **Step 4: รัน test เพื่อยืนยันว่าผ่าน**

Run: `composer test -- --filter='testCancelWith(Cancel|Por|Yut)WordInConversation'`
Expected: 3 PASS

- [ ] **Step 5: แก้ testConversationCancelInThai ให้ match ข้อความใหม่**

เปลี่ยน assertion:
```php
$this->assertStringContainsString('ยกเลิกการสอนการใช้งานแล้ว', $messages[1]->getText());
```
เป็น:
```php
$this->assertStringContainsString('ยกเลิกแล้ว', $messages[1]->getText());
```

- [ ] **Step 6: รัน tests ทั้งหมด**

Run: `composer test`
Expected: 36 tests, all PASS

- [ ] **Step 7: Commit**

```bash
git add src/Conversations/BaseConversation.php tests/Feature/BotResponseTest.php
git commit -m "feat: expand cancel triggers to 4 words + update cancel message for nongweb"
```

---

### Task 2: OnboardingConversation — 5 steps + nongweb tone

**Files:**
- Modify: `src/Conversations/OnboardingConversation.php`
- Test: `tests/Feature/BotResponseTest.php`

- [ ] **Step 1: เขียน test สำหรับ 5-step flow**

เพิ่ม method:
```php
    public function testOnboardingFiveSteps(): void
    {
        $cache = new ArrayCache();
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());

        $this->fakeDriver->messages = [new IncomingMessage('สมชาย', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(2, count($messages));
        $this->assertStringContainsString('สมชาย', $messages[1]->getText());
    }
```

- [ ] **Step 2: รัน test เพื่อยืนยันว่าล้มเหลว**

Run: `composer test -- --filter='testOnboardingFiveSteps'`
Expected: 1 FAILURE ("ดีใจที่ได้รู้จัก" not found)

- [ ] **Step 3: เขียน OnboardingConversation ใหม่ทั้งหมด**

```php
<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class OnboardingConversation extends BaseConversation
{
    protected $name;

    public function run()
    {
        $this->say('สวัสดีค่า~ ดีใจที่ได้รู้จักนะคะ! 💕');
        $this->askName();
    }

    protected function askName()
    {
        $this->ask('ขอชื่อเล่นหน่อยได้ไหมคะ? เรียกอะไรดีเอ่ย~', function (Answer $answer) {
            $this->name = trim($answer->getText()) ?: 'เพื่อน';
            $this->say("ยินดีที่ได้รู้จัก {$this->name} นะค้าา~ 💕");
            $this->showCapabilities();
        });
    }

    protected function showCapabilities()
    {
        $this->say("{$this->name} รู้ยัง~ น้องเวบทำอะไรได้บ้าง? 💖\n".
            "• ส่งรูปภาพมาให้ดู\n".
            "• ส่งตำแหน่งที่ตั้ง\n".
            "• ส่งสติกเกอร์น่ารักๆ\n".
            "• พิมพ์ ยกเลิก เมื่อไหร่ก็ได้เลย");
        $this->finish();
    }

    protected function finish()
    {
        $this->say('พร้อมช่วยแล้วนะค้าา~ ถ้าอยากรู้อะไรเพิ่มเติมพิมพ์ ช่วยเหลือ ได้เลยนะคะ 😘');
    }

    public function skipsConversation(IncomingMessage $message)
    {
        return trim($message->getText()) === 'ข้าม';
    }
}
```

- [ ] **Step 4: อัปเดต test assertions ที่อ้างถึงข้อความเก่า**

เปลี่ยนใน `testOnboardInThai`, `testOnboardingInThai`, `testOnboardStartsConversation`, `testOnboardingStartsConversation`, `testConversationAsksForName`:

```php
$this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
```
(แทนที่ `assertStringContainsString('สอนการใช้งาน', ...)`)

และใน `testConversationResumedAcrossInstancesWithSharedCache`:
```php
$this->assertStringContainsString('ยินดีที่ได้รู้จัก', $messages[1]->getText());
```
(แทนที่ `assertStringContainsString('บอท', ...)`)

และใน `testConversationCancelInThai`:
```php
$this->assertStringNotContainsString('พร้อมช่วย', $msg->getText());
```
(แทนที่ `assertStringNotContainsString('สรุปการสอนการใช้งาน', ...)`)

- [ ] **Step 5: รัน tests ทั้งหมด**

Run: `composer test`
Expected: all PASS

- [ ] **Step 6: Commit**

```bash
git add src/Conversations/OnboardingConversation.php tests/Feature/BotResponseTest.php
git commit -m "feat: rewrite OnboardingConversation to 5-step nongweb flow"
```

---

### Task 3: index.php — handlers ทั้งหมด + new triggers + media handlers

**Files:**
- Modify: `public/index.php`
- Modify: `tests/Feature/BotResponseTest.php` (registerHandlers + test methods)

- [ ] **Step 1: เขียน tests ทั้งหมดล่วงหน้า**

อัปเดต `registerHandlers` ใน `BotResponseTest.php`:
```php
    protected function registerHandlers(BotMan $botman): void
    {
        // Greeting — multiple triggers
        foreach (['สวัสดี', 'ไง', 'hello', 'หวัดดี', 'เฮ้ย'] as $trigger) {
            $botman->hears($trigger, function ($bot) {
                $question = Question::create('สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?')
                    ->addButton(Button::create('แนะนำตัว')->value('แนะนำตัว'))
                    ->addButton(Button::create('ดูความสามารถ')->value('ดูความสามารถ'))
                    ->addButton(Button::create('ช่วยเหลือ')->value('ช่วยเหลือ'));
                $bot->reply($question);
            });
        }

        // แนะนำตัว — multiple triggers
        foreach (['แนะนำตัว', 'รู้จัก', 'เป็นใคร'] as $trigger) {
            $botman->hears($trigger, function ($bot) {
                $bot->startConversation(new OnboardingConversation());
            });
        }

        // ดูความสามารถ — multiple triggers
        foreach (['ดูความสามารถ', 'ทำอะไรได้บ้าง', 'สอนการใช้งาน', 'help'] as $trigger) {
            $botman->hears($trigger, function ($bot) {
                $bot->reply("โอ้ยยย น้องดีใจที่คุณอยากรู้ความสามารถของน้องเลยค่าา~ 💖\n".
                    "ลองทำตามนี้ได้เลยนะคะ:\n".
                    "• ส่งรูปภาพมา\n".
                    "• ส่งตำแหน่งที่ตั้ง\n".
                    "• ส่งสติกเกอร์\n".
                    "• พิมพ์ ยกเลิก (เมื่อไหร่ก็ได้)\n".
                    "หรือจะคุยเล่นก็ได้เลยค่าาา 😘");
            });
        }

        // ช่วยเหลือ — multiple triggers
        foreach (['ช่วยเหลือ', 'เมนู', 'menu'] as $trigger) {
            $botman->hears($trigger, function ($bot) {
                $bot->reply("นี่เลยย~ รายการที่ทำได้ทั้งหมดนะค้าา 💕\n".
                    "• ส่งรูปภาพ / สติกเกอร์ / ตำแหน่ง\n".
                    "• พิมพ์ ช่วยเหลือ\n".
                    "• พิมพ์ ยกเลิก\n".
                    "ลองทำอะไรก็ได้เลยค่าาา~");
            });
        }

        // Image
        $botman->hears('\[Image\]', function ($bot) {
            $bot->reply('ว้าววว~ รูปสวยมากเลยค่าาา 💕 รับรูปแล้วนะคะ อยากให้เวบช่วยอะไรกับรูปนี้ดีคะ?');
        });

        // Sticker
        $botman->hears('\[Sticker\]', function ($bot) {
            $bot->reply('น่ารักกกก สติกเกอร์น่ารักมากเลยค่าาา 😂💕');
        });

        // Location
        $botman->hears('\[Location\]', function ($bot) {
            $bot->reply('ได้รับตำแหน่งแล้วค่าาา 📍 ใกล้ ๆ นี้เลยเหรอ ขาาา อยากให้ช่วยหาข้อมูลอะไรตรงนี้ไหมคะ?');
        });

        // Fallback
        $botman->fallback(function ($bot) {
            $bot->reply("อ๊ะ~ น้องเวบยังไม่ค่อยเข้าใจเลยค่ะ 😅\n".
                "ลองพิมพ์ *ช่วยเหลือ* ดูนะค้าาา จะได้แสดงสิ่งที่น้องทำได้ทั้งหมดเลย");
        });
    }
```

เพิ่ม test methods ใหม่:
```php
    // === GREETING TRIGGER VARIANTS ===
    public function testGreetingWithSawadee(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('สวัสดี', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertInstanceOf(Question::class, $messages[0]);
        $this->assertStringContainsString('น้องเวบ', $messages[0]->getText());
    }

    public function testGreetingWithNgai(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('ไง', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertInstanceOf(Question::class, $messages[0]);
        $this->assertStringContainsString('น้องเวบ', $messages[0]->getText());
    }

    public function testGreetingWithHello(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('hello', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertInstanceOf(Question::class, $messages[0]);
        $this->assertStringContainsString('น้องเวบ', $messages[0]->getText());
    }

    public function testGreetingWithWadDee(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('หวัดดี', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertInstanceOf(Question::class, $messages[0]);
        $this->assertStringContainsString('น้องเวบ', $messages[0]->getText());
    }

    public function testGreetingWithHey(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('เฮ้ย', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertInstanceOf(Question::class, $messages[0]);
        $this->assertStringContainsString('น้องเวบ', $messages[0]->getText());
    }

    // === ONBOARD TRIGGER VARIANTS ===
    public function testOnboardWithRoojak(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('รู้จัก', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
    }

    public function testOnboardWithPenKrai(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('เป็นใคร', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(1, count($messages));
        $this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
    }

    // === CAPABILITY TRIGGER VARIANTS ===
    public function testCapabilityWithDu(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('ดูความสามารถ', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertStringContainsString('ความสามารถ', $messages[0]->getText());
    }

    public function testCapabilityWithThaArai(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('ทำอะไรได้บ้าง', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertStringContainsString('ความสามารถ', $messages[0]->getText());
    }

    public function testCapabilityWithHelp(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('help', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertStringContainsString('ความสามารถ', $messages[0]->getText());
    }

    // === HELP TRIGGER VARIANTS ===
    public function testHelpWithMenu(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('เมนู', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertStringContainsString('รายการ', $messages[0]->getText());
    }

    public function testHelpWithMenuEnglish(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('menu', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertStringContainsString('รายการ', $messages[0]->getText());
    }

    // === MEDIA HANDLERS ===
    public function testStickerHandlerReplies(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('[Sticker]', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('สติกเกอร์', $messages[0]->getText());
    }

    public function testLocationHandlerReplies(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('[Location]', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('ตำแหน่ง', $messages[0]->getText());
    }
```

- [ ] **Step 2: รัน tests เพื่อยืนยันว่าล้มเหลว**

Run: `composer test`
Expected: 16+ FAILURES (old text not matching new handlers)

- [ ] **Step 3: แก้ public/index.php**

เปลี่ยนทั้งไฟล์ — อัปเดต:
- Greeting handler: loop 5 triggers, new Question text, new button labels (แนะนำตัว→ดูความสามารถ)
- Recommend handler: loop 3 triggers (แนะนำตัว, รู้จัก, เป็นใคร)
- Capability handler: loop 4 triggers (ดูความสามารถ, ทำอะไรได้บ้าง, สอนการใช้งาน, help)
- Help handler: loop 3 triggers (ช่วยเหลือ, เมนู, menu)
- Image response
- Add Sticker handler
- Add Location handler
- Fallback text

**Greeting section:** (replace lines 96-103)
```php
foreach (['สวัสดี', 'ไง', 'hello', 'หวัดดี', 'เฮ้ย'] as $trigger) {
    $botman->hears($trigger, function ($bot) {
        Logger::log('HEARS_MATCH', ['command' => $trigger, 'handler' => 'greeting']);
        $question = Question::create('สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?')
            ->addButton(Button::create('แนะนำตัว')->value('แนะนำตัว'))
            ->addButton(Button::create('ดูความสามารถ')->value('ดูความสามารถ'))
            ->addButton(Button::create('ช่วยเหลือ')->value('ช่วยเหลือ'));
        $bot->reply($question);
    });
}
```

**แนะนำตัว section:** (replace lines 105-108)
```php
foreach (['แนะนำตัว', 'รู้จัก', 'เป็นใคร'] as $trigger) {
    $botman->hears($trigger, function ($bot) {
        Logger::log('HEARS_MATCH', ['command' => $trigger, 'handler' => 'onboarding']);
        $bot->startConversation(new OnboardingConversation());
    });
}
```

**ดูความสามารถ section:** (replace lines 110-113)
```php
foreach (['ดูความสามารถ', 'ทำอะไรได้บ้าง', 'สอนการใช้งาน', 'help'] as $trigger) {
    $botman->hears($trigger, function ($bot) {
        Logger::log('HEARS_MATCH', ['command' => $trigger, 'handler' => 'capability']);
        $bot->reply("โอ้ยยย น้องดีใจที่คุณอยากรู้ความสามารถของน้องเลยค่าา~ 💖\n".
            "ลองทำตามนี้ได้เลยนะคะ:\n".
            "• ส่งรูปภาพมา\n".
            "• ส่งตำแหน่งที่ตั้ง\n".
            "• ส่งสติกเกอร์\n".
            "• พิมพ์ ยกเลิก (เมื่อไหร่ก็ได้)\n".
            "หรือจะคุยเล่นก็ได้เลยค่าาา 😘");
    });
}
```

**Image section:** (replace lines 115-118)
```php
$botman->hears('\[Image\]', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'image']);
    $bot->reply('ว้าววว~ รูปสวยมากเลยค่าาา 💕 รับรูปแล้วนะคะ อยากให้เวบช่วยอะไรกับรูปนี้ดีคะ?');
});
```

**ช่วยเหลือ section:** (replace lines 120-123)
```php
foreach (['ช่วยเหลือ', 'เมนู', 'menu'] as $trigger) {
    $botman->hears($trigger, function ($bot) {
        Logger::log('HEARS_MATCH', ['command' => $trigger, 'handler' => 'help']);
        $bot->reply("นี่เลยย~ รายการที่ทำได้ทั้งหมดนะค้าา 💕\n".
            "• ส่งรูปภาพ / สติกเกอร์ / ตำแหน่ง\n".
            "• พิมพ์ ช่วยเหลือ\n".
            "• พิมพ์ ยกเลิก\n".
            "ลองทำอะไรก็ได้เลยค่าาา~");
    });
}
```

**เพิ่ม Sticker handler:** (after Image section)
```php
$botman->hears('\[Sticker\]', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'sticker']);
    $bot->reply('น่ารักกกก สติกเกอร์น่ารักมากเลยค่าาา 😂💕');
});
```

**เพิ่ม Location handler:** (after Sticker section)
```php
$botman->hears('\[Location\]', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'location']);
    $bot->reply('ได้รับตำแหน่งแล้วค่าาา 📍 ใกล้ ๆ นี้เลยเหรอ ขาาา อยากให้ช่วยหาข้อมูลอะไรตรงนี้ไหมคะ?');
});
```

**Fallback:** (replace lines 125-128)
```php
$botman->fallback(function ($bot) {
    Logger::log('FALLBACK', ['text' => $bot->getMessage()->getText()]);
    $bot->reply("อ๊ะ~ น้องเวบยังไม่ค่อยเข้าใจเลยค่ะ 😅\n".
        "ลองพิมพ์ *ช่วยเหลือ* ดูนะค้าาา จะได้แสดงสิ่งที่น้องทำได้ทั้งหมดเลย");
});
```

- [ ] **Step 4: แก้ test assertions เก่าทั้งหมด**

อัปเดต test methods ที่เหลือใน `BotResponseTest.php`:
- `testHiRepliesWithQuickReplyButtons` → question text, button labels (คำสั่ง→ดูความสามารถ)
- `testHiInThai` → question text, button labels
- `testUnknownMessageFallsBack` → new fallback text
- `testImageHandlerReplies` → new image response
- `testHelpInThai` → new help text
- `testHelpReplies` → new help text
- `testCancelWithoutConversationFallsBack` → new fallback text
- `testConversationNotResumedAcrossInstancesWithIsolatedCache` → new fallback text

- [ ] **Step 5: รัน tests ทั้งหมด**

Run: `composer test`
Expected: 50+ tests, all PASS

- [ ] **Step 6: Commit**

```bash
git add public/index.php tests/Feature/BotResponseTest.php
git commit -m "feat: nongweb personality for all handlers + new triggers + Sticker/Location handlers"
```
