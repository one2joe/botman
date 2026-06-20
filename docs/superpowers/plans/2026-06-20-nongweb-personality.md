# น้องเวบ Personality Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** เปลี่ยนบุคลิก LINE Bot จากสุภาพเป็น "น้องเวบ" casual หญิง + เพิ่ม triggers และ media handlers

**Architecture:** แก้ไขตรงๆ ที่ handler closures ใน `public/index.php` และ `OnboardingConversation.php` โดยไม่เพิ่ม abstraction layer ใหม่ เปลี่ยน cancel check ใน `BaseConversation.php`

**Tech Stack:** PHP 8.4, BotMan, LINE Driver, PHPUnit 11

---

### Task 1: BaseConversation — Cancel via quick reply only + ข้อความใหม่

**Files:**
- Modify: `src/Conversations/BaseConversation.php`
- Test: `tests/Feature/BotResponseTest.php`

- [ ] **Step 1: เขียน test ล่วงหน้าสำหรับ cancel mechanism ใหม่**

ลบ 3 tests จาก Task 1 เดิม เพิ่ม 3 tests แทน:

```php
    public function testCancelViaButtonInConversation(): void
    {
        $cache = new ArrayCache();
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $this->fakeDriver->messages = [(new IncomingMessage('cancel', 'Utest', 'token'))->setIsInteractiveReply(true)];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(3, count($messages));
        $this->assertStringContainsString('ยกเลิกแล้ว', $messages[2]->getText());
    }

    public function testCancelTextPorIgnoredInConversation(): void
    {
        $cache = new ArrayCache();
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $this->fakeDriver->messages = [new IncomingMessage('พอ', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(3, count($messages));
        $this->assertStringNotContainsString('ยกเลิกแล้ว', $messages[2]->getText());
    }

    public function testCancelTextYutIgnoredInConversation(): void
    {
        $cache = new ArrayCache();
        $this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $this->fakeDriver->messages = [new IncomingMessage('หยุด', 'Utest', 'token')];
        $this->createBot($cache)->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertGreaterThanOrEqual(3, count($messages));
        $this->assertStringNotContainsString('ยกเลิกแล้ว', $messages[2]->getText());
    }
```

- [ ] **Step 2: รัน test เพื่อยืนยันว่าล้มเหลว**

Run: `composer test -- --filter='testCancel(ViaButton|TextPor|TextYut)'`
Expected: 2 FAILURES (text-ignored tests pass by luck; button test fails because cancel check not implemented yet)

- [ ] **Step 3: แก้ BaseConversation.php**

เปลี่ยนจาก:
```php
if (trim($answer->getText()) === 'ยกเลิก') {
    $this->bot->reply('ยกเลิกการสอนการใช้งานแล้ว');
```
เป็น:
```php
if ($answer->isInteractiveMessageReply() && $answer->getValue() === 'cancel') {
    $this->bot->reply('ยกเลิกแล้วนะคะ~ ✅ ไม่เป็นไรเลยค่าาา อยากทำอะไรต่อบอกน้องได้เลย');
```

- [ ] **Step 4: รัน test เพื่อยืนยันว่าผ่าน**

Run: `composer test -- --filter='testCancel(ViaButton|TextPor|TextYut)'`
Expected: 3 PASS

- [ ] **Step 5: รัน tests ทั้งหมด (คาดว่าล้มเหลวเพราะ test อื่นๆ ยังไม่แก้)**

Run: `composer test`
Expected: some FAILURES (from old test assertions not matching new response text)

- [ ] **Step 6: Commit**

```bash
git add src/Conversations/BaseConversation.php tests/Feature/BotResponseTest.php
git commit -m "feat: change cancel to quick reply only + nongweb cancel message"
```

---

### Task 2: OnboardingConversation — 5 steps + nongweb tone + askGoal

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
        $this->assertGreaterThanOrEqual(3, count($messages));
        $this->assertStringContainsString('สมชาย', $messages[2]->getText());
    }
```

- [ ] **Step 2: รัน test เพื่อยืนยันว่าล้มเหลว**

Run: `composer test -- --filter='testOnboardingFiveSteps'`
Expected: 1 FAILURE ("ดีใจที่ได้รู้จัก" not found — old Onboarding says "เริ่มสอนการใช้งาน")

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
    protected $goal;

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
            $this->askGoal();
        });
    }

    protected function askGoal()
    {
        $question = Question::create('สนใจอยากให้บอทช่วยอะไรเป็นหลัก?')
            ->addButton(Button::create('คุยเล่น')->value('คุยเล่น'))
            ->addButton(Button::create('ลองส่งรูป')->value('ลองส่งรูป'))
            ->addButton(Button::create('ทดสอบ webhook')->value('ทดสอบ webhook'))
            ->addButton(Button::create('ข้าม')->value('ข้าม'))
            ->addButton(Button::create('ยกเลิก')->value('cancel'));

        $this->ask($question, function (Answer $answer) {
            if (trim($answer->getText()) === 'ข้าม') {
                $this->goal = 'คุยเล่น';
            } else {
                $this->goal = trim($answer->getText()) ?: 'คุยเล่น';
            }
            $this->say("{$this->name} เลือก {$this->goal} ไว้ใช่มั้ยเอ่ย~ พร้อมช่วยแล้วนะค้าาา ถ้าอยากรู้อะไรเพิ่มเติมพิมพ์ ช่วยเหลือ ได้เลยนะคะ 😘");
        });
    }
}
```

- [ ] **Step 4: อัปเดต test assertions ทั้งหมดให้ตรงกับ Onboarding ใหม่และข้อความเปลี่ยน**

**testOnboardInThai** ('แนะนำตัว'):
```php
$this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
```

**testOnboardingInThai** → เปลี่ยน trigger จาก `'สอนการใช้งาน'` → `'แนะนำตัว'`, assertion เหมือน testOnboardInThai:
```php
$this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
...
$this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
```

**testOnboardStartsConversation** ('แนะนำตัว'):
```php
$this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
```

**testOnboardingStartsConversation** → เปลี่ยน trigger จาก `'สอนการใช้งาน'` → `'แนะนำตัว'`:
```php
$this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
...
$this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
```

**testConversationAsksForName** → เปลี่ยน trigger จาก `'สอนการใช้งาน'` → `'แนะนำตัว'`:
```php
$this->fakeDriver->messages = [new IncomingMessage('แนะนำตัว', 'Utest', 'token')];
...
$this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
$this->assertStringContainsString('ชื่อ', $messages[1]->getText());  // index 0→1 because welcome_say is now index 0
```

**testConversationCancelInThai** → เปลี่ยน assertions ให้ตรงกับ extra say() message:
```php
$this->assertGreaterThanOrEqual(3, count($messages));
$this->assertStringContainsString('ดีใจที่ได้รู้จัก', $messages[0]->getText());
$this->assertStringContainsString('ยกเลิกแล้ว', $messages[2]->getText());  // index 1→2 because welcome_say is now index 0
foreach ($messages as $msg) {
    $this->assertStringNotContainsString('พร้อมช่วย', $msg->getText());  // replaces 'สรุปการสอนการใช้งาน'
}
```

หมายเหตุ: testConversationCancelInThai ยังคงส่ง `IncomingMessage('ยกเลิก', ...)` โดยไม่ set interactive reply — เปลี่ยนเป็น `->setIsInteractiveReply(true)` ด้วย มิฉะนั้น cancel จะไม่ทำงาน

**testCancelWithoutConversationFallsBack** → fallback message ใหม่:
```php
$this->assertStringContainsString('น้องเวบยังไม่ค่อยเข้าใจ', $messages[0]->getText());
```

**testConversationNotResumedAcrossInstancesWithIsolatedCache** → fallback message ใหม่:
```php
$this->assertStringContainsString('น้องเวบยังไม่ค่อยเข้าใจ', $messages[1]->getText());
```

**testConversationResumedAcrossInstancesWithSharedCache** → assertion เปลี่ยนเพราะ extra say():
```php
$this->assertGreaterThanOrEqual(3, count($messages));
$this->assertStringContainsString('ชื่อ', $messages[1]->getText());  // index 0→1
$this->assertStringContainsString('ยินดีที่ได้รู้จัก', $messages[2]->getText());  // index 1→2
```

- [ ] **Step 5: รัน tests ทั้งหมดที่แก้แล้ว**

Run: `composer test`
Expected: all PASS (เฉพาะ tests ที่เกี่ยวข้องกับ Task 1-2)

- [ ] **Step 6: Commit**

```bash
git add src/Conversations/OnboardingConversation.php tests/Feature/BotResponseTest.php
git commit -m "feat: rewrite OnboardingConversation to 5-step nongweb flow with askGoal"
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

        // Onboarding — multiple triggers
        foreach (['แนะนำตัว', 'รู้จัก', 'เป็นใคร'] as $trigger) {
            $botman->hears($trigger, function ($bot) {
                $bot->startConversation(new OnboardingConversation());
            });
        }

        // Capability — multiple triggers
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

        // Help — multiple triggers
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

    public function testCapabilityWithSon(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('สอนการใช้งาน', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertStringContainsString('ความสามารถ', $messages[0]->getText());
    }

    // === HELP TRIGGER VARIANTS ===
    public function testHelpWithChuayLuer(): void
    {
        $this->fakeDriver->messages = [new IncomingMessage('ช่วยเหลือ', 'Utest', 'token')];
        $this->createBot()->listen();
        $messages = $this->fakeDriver->getBotMessages();
        $this->assertStringContainsString('รายการ', $messages[0]->getText());
    }

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
Expected: 15+ FAILURES (old handlers not matching new text + missing new triggers)

- [ ] **Step 3: แก้ public/index.php**

เปลี่ยนทั้งไฟล์:

**Greeting section:**
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

**Onboarding section:**
```php
foreach (['แนะนำตัว', 'รู้จัก', 'เป็นใคร'] as $trigger) {
    $botman->hears($trigger, function ($bot) {
        Logger::log('HEARS_MATCH', ['command' => $trigger, 'handler' => 'onboarding']);
        $bot->startConversation(new OnboardingConversation());
    });
}
```

**Capability section:**
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

**Image section:**
```php
$botman->hears('\[Image\]', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'image']);
    $bot->reply('ว้าววว~ รูปสวยมากเลยค่าาา 💕 รับรูปแล้วนะคะ อยากให้เวบช่วยอะไรกับรูปนี้ดีคะ?');
});
```

**Help section:**
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

**Sticker handler:**
```php
$botman->hears('\[Sticker\]', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'sticker']);
    $bot->reply('น่ารักกกก สติกเกอร์น่ารักมากเลยค่าาา 😂💕');
});
```

**Location handler:**
```php
$botman->hears('\[Location\]', function ($bot) {
    Logger::log('HEARS_MATCH', ['command' => 'location']);
    $bot->reply('ได้รับตำแหน่งแล้วค่าาา 📍 ใกล้ ๆ นี้เลยเหรอ ขาาา อยากให้ช่วยหาข้อมูลอะไรตรงนี้ไหมคะ?');
});
```

**Fallback:**
```php
$botman->fallback(function ($bot) {
    Logger::log('FALLBACK', ['text' => $bot->getMessage()->getText()]);
    $bot->reply("อ๊ะ~ น้องเวบยังไม่ค่อยเข้าใจเลยค่ะ 😅\n".
        "ลองพิมพ์ *ช่วยเหลือ* ดูนะค้าาา จะได้แสดงสิ่งที่น้องทำได้ทั้งหมดเลย");
});
```

- [ ] **Step 4: แก้ test assertions เก่าทั้งหมด**

**testHiRepliesWithQuickReplyButtons:**
```php
$this->assertSame('สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?', $question->getText());
$this->assertContains('ดูความสามารถ', $labels);  // replaces 'คำสั่ง'
$this->assertContains('ช่วยเหลือ', $labels);     // replaces 'ทักทาย'
```

**testHiInThai:**
```php
$this->assertSame('สวัสดีค่าาา~ 💕 น้องเวบเองนะค้าา มาเจอกันอีกแล้วว~ มีอะไรให้ช่วยเหลือไหมคะ?', $messages[0]->getText());
$this->assertContains('ดูความสามารถ', $labels);  // replaces 'คำสั่ง'
$this->assertContains('ช่วยเหลือ', $labels);     // replaces 'ทักทาย'
```

**testUnknownMessageFallsBack:**
```php
$this->assertStringContainsString('น้องเวบยังไม่ค่อยเข้าใจ', $messages[0]->getText());
$this->assertStringContainsString('ช่วยเหลือ', $messages[0]->getText());
```

**testImageHandlerReplies:**
```php
$this->assertStringContainsString('รูปสวยมากเลย', $messages[0]->getText());
```

**testHelpInThai:**
```php
$this->assertStringContainsString('รายการ', $messages[0]->getText());  // replaces 'สวัสดี'
$this->assertStringContainsString('ส่งรูปภาพ', $messages[0]->getText());  // replaces 'แนะนำตัว'
```

**testHelpReplies:**
```php
$this->assertStringContainsString('รายการ', $messages[0]->getText());  // replaces 'สวัสดี'
$this->assertStringContainsString('ส่งรูปภาพ', $messages[0]->getText());  // replaces 'แนะนำตัว'
```

**testCancelWithoutConversationFallsBack:**
```php
$this->assertStringContainsString('น้องเวบยังไม่ค่อยเข้าใจ', $messages[0]->getText());  // replaces 'ไม่เข้าใจครับ'
```

**testConversationNotResumedAcrossInstancesWithIsolatedCache:**
```php
$this->assertStringContainsString('น้องเวบยังไม่ค่อยเข้าใจ', $messages[1]->getText());  // replaces 'ไม่เข้าใจครับ'
```

- [ ] **Step 5: รัน tests ทั้งหมด**

Run: `composer test`
Expected: 50+ tests, all PASS

- [ ] **Step 6: Commit**

```bash
git add public/index.php tests/Feature/BotResponseTest.php
git commit -m "feat: nongweb personality for all handlers + new triggers + Sticker/Location handlers"
```
