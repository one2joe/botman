<?php

namespace App;

use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\BotMan;

class LogMiddleware implements MiddlewareInterface
{
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        Logger::debug('RECEIVED', [
            'text' => $message->getText(),
            'sender' => $message->getSender(),
            'recipient' => $message->getRecipient(),
        ]);
        return $next($message);
    }

    public function matching(IncomingMessage $message, $pattern, $regexMatched)
    {
        Logger::debug('MATCHING', [
            'text' => $message->getText(),
            'pattern' => $pattern,
            'regexMatched' => $regexMatched,
        ]);
        return $regexMatched;
    }

    public function heard(IncomingMessage $message, $next, BotMan $bot)
    {
        Logger::log('HEARD', [
            'text' => $message->getText(),
        ]);
        return $next($message);
    }

    public function sending($payload, $next, BotMan $bot)
    {
        Logger::log('SENDING', [
            'payload_type' => is_object($payload) ? get_class($payload) : gettype($payload),
            'payload' => $payload,
        ]);
        return $next($payload);
    }

    public function captured(IncomingMessage $message, $next, BotMan $bot)
    {
        Logger::debug('CAPTURED', [
            'text' => $message->getText(),
        ]);
        return $next($message);
    }
}
