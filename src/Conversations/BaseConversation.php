<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;

abstract class BaseConversation extends Conversation
{
    public function ask($question, $next, $additionalParameters = [])
    {
        if ($next instanceof \Closure) {
            $original = $next;
            $next = function (Answer $answer) use ($original) {
                if (trim($answer->getText()) === 'ยกเลิก') {
                    $this->bot->reply('ยกเลิกการสอนการใช้งานแล้ว');
                    return;
                }
                $rebound = \Closure::bind($original, $this, $this);
                return $rebound($answer);
            };
        }

        return parent::ask($question, $next, $additionalParameters);
    }
}
