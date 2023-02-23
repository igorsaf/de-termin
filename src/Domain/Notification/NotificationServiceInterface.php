<?php

namespace DeTermin\Domain\Notification;

interface NotificationServiceInterface
{
    public function sendNotificationWithPhoto(NotificationMessage $message);
}
