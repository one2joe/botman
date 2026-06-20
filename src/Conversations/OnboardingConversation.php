<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;

class OnboardingConversation extends Conversation
{
    protected $name;
    protected $goal;
    protected $experience;

    public function run()
    {
        $this->bot->reply('เริ่ม onboarding ได้เลย พิมพ์ `cancel` เมื่อไหร่ก็ได้เพื่อหยุด');

        $this->ask(
            'ก่อนเริ่ม ขอชื่อเล่นหน่อยครับ',
            function (Answer $answer) {
            $this->name = trim($answer->getText()) ?: 'เพื่อน';
            $this->askGoal();
            }
        );
    }

    protected function askGoal()
    {
        $question = Question::create('อยากใช้บอทนี้ทำอะไรเป็นหลัก?')
            ->addButtons([
                ['text' => 'ทดสอบ webhook', 'value' => 'ทดสอบ webhook'],
                ['text' => 'คุยกับผู้ใช้', 'value' => 'คุยกับผู้ใช้'],
                ['text' => 'ลองคำสั่ง', 'value' => 'ลองคำสั่ง'],
            ]);

        $this->ask($question, function (Answer $answer) {
            $this->goal = trim($answer->getText()) ?: 'ทดลองใช้งาน';
            $this->askExperience();
        });
    }

    protected function askExperience()
    {
        $question = Question::create('ตอนนี้คุ้นกับ BotMan ระดับไหน?')
            ->addButtons([
                ['text' => 'เริ่มใหม่', 'value' => 'เริ่มใหม่'],
                ['text' => 'พอทำได้', 'value' => 'พอทำได้'],
                ['text' => 'คล่องแล้ว', 'value' => 'คล่องแล้ว'],
            ]);

        $this->ask($question, function (Answer $answer) {
            $this->experience = $answer->getText();
            $this->bot->reply(
                "สรุป onboarding\n".
                "ชื่อเล่น: {$this->name}\n".
                "เป้าหมาย: {$this->goal}\n".
                "ระดับความคุ้นเคย: {$this->experience}\n\n".
                "พิมพ์ `help` เพื่อดูคำสั่งทดสอบ หรือ `onboard` เพื่อเริ่มใหม่ได้เลย"
            );
        });
    }

    public function stopsConversation(IncomingMessage $message)
    {
        return strtolower(trim($message->getText())) === 'cancel';
    }

    public function skipsConversation(IncomingMessage $message)
    {
        return strtolower(trim($message->getText())) === 'skip';
    }
}
