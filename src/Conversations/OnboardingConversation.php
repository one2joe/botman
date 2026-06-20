<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;

class OnboardingConversation extends Conversation
{
    protected $name;
    protected $goal;
    protected $experience;

    public function run()
    {
        $this->ask(
            "เริ่มสอนการใช้งานได้เลย พิมพ์ \"ยกเลิก\" เมื่อไหร่ก็ได้เพื่อหยุด\n\nก่อนเริ่ม ขอชื่อเล่นหน่อยครับ",
            function (Answer $answer) {
            $this->name = trim($answer->getText()) ?: 'เพื่อน';
            $this->askGoal();
            }
        );
    }

    protected function askGoal()
    {
        $question = Question::create('อยากใช้บอทนี้ทำอะไรเป็นหลัก?')
            ->addButton(Button::create('ทดสอบ webhook')->value('ทดสอบ webhook'))
            ->addButton(Button::create('คุยกับผู้ใช้')->value('คุยกับผู้ใช้'))
            ->addButton(Button::create('ลองคำสั่ง')->value('ลองคำสั่ง'));

        $this->ask($question, function (Answer $answer) {
            $this->goal = trim($answer->getText()) ?: 'ทดลองใช้งาน';
            $this->askExperience();
        });
    }

    protected function askExperience()
    {
        $question = Question::create('ตอนนี้คุ้นกับ BotMan ระดับไหน?')
            ->addButton(Button::create('เริ่มใหม่')->value('เริ่มใหม่'))
            ->addButton(Button::create('พอทำได้')->value('พอทำได้'))
            ->addButton(Button::create('คล่องแล้ว')->value('คล่องแล้ว'));

        $this->ask($question, function (Answer $answer) {
            $this->experience = $answer->getText();
            $this->bot->reply(
                "สรุปการสอนการใช้งาน\n".
                "ชื่อเล่น: {$this->name}\n".
                "เป้าหมาย: {$this->goal}\n".
                "ระดับความคุ้นเคย: {$this->experience}\n\n".
                'พิมพ์ "ช่วยเหลือ" เพื่อดูคำสั่ง หรือ "แนะนำตัว" เพื่อเริ่มใหม่ได้เลย'
            );
        });
    }

    public function stopsConversation(IncomingMessage $message)
    {
        return trim($message->getText()) === 'ยกเลิก';
    }

    public function skipsConversation(IncomingMessage $message)
    {
        return trim($message->getText()) === 'ข้าม';
    }
}
