<?php

namespace DeTermin\Domain\Notification;

final class NotificationMessage
{
    public function __construct(
        private readonly string $message,
        private readonly string $photo
    ){}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getPhoto(): string
    {
        return $this->photo;
    }
}
