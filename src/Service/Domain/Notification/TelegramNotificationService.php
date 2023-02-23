<?php

declare(strict_types=1);

namespace DeTermin\Service\Domain\Notification;

use DeTermin\Domain\Notification\NotificationMessage;
use DeTermin\Domain\Notification\NotificationServiceInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TelegramNotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUri,
        private readonly string $botId,
        private readonly string $botToken,
        private readonly string $chatId
    ) {}


    public function sendNotificationWithPhoto(NotificationMessage $message)
    {
        try {
            $url = sprintf('%s/bot%s:%s/sendPhoto', $this->baseUri, $this->botId, $this->botToken);
            $data = new FormDataPart([
                'chat_id' => $this->chatId,
                'caption' => $message->getMessage(),
                'photo' => DataPart::fromPath($message->getPhoto())
            ]);

            $this->httpClient->request('POST', $url, [
                'headers' => $data->getPreparedHeaders()->toArray(),
                'body' => $data->bodyToIterable()
            ]);
        } catch (TransportExceptionInterface) {}
    }
}
