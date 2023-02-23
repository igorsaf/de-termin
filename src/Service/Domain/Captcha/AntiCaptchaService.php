<?php

namespace DeTermin\Service\Domain\Captcha;

use DeTermin\Domain\Captcha\CaptchaImage;
use DeTermin\Domain\Captcha\CaptchaServiceInterface;
use DeTermin\Domain\Captcha\CaptchaSolved;
use DeTermin\Domain\Captcha\Exceptions\CaptchaNotSolvedException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AntiCaptchaService implements CaptchaServiceInterface
{
    private const MAX_CHECK_ATTEMPTS = 5;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUri,
        private readonly string $key
    )
    {
    }

    public function solveCaptcha(CaptchaImage $captchaImage): ?CaptchaSolved
    {
        $taskId = $this->createCaptchaSolvingTask($captchaImage);
        if ($taskId === null) {
            return null;
        }

        for ($i = 0; $i < self::MAX_CHECK_ATTEMPTS; $i++) {
            sleep(3);
            $solvingResult = $this->getCaptchaSolvingResult($taskId);
            if ($solvingResult !== null) {
                return $solvingResult;
            }
        }

        return null;
    }

    private function createCaptchaSolvingTask(CaptchaImage $captchaImage): ?AntiCaptchaTaskId
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf('%s/createTask', $this->baseUri),
                [
                    'headers' => [
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ],
                    'json' => [
                        'clientKey' => $this->key,
                        'task' => [
                            'type' => 'ImageToTextTask',
                            'body' => $captchaImage->getImageBase64()
                        ]
                    ]
                ]
            );

            $result = $response->toArray();

            return new AntiCaptchaTaskId((int)$result['taskId']);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }
    }

    private function getCaptchaSolvingResult(AntiCaptchaTaskId $taskId): ?CaptchaSolved
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf('%s/getTaskResult', $this->baseUri),
                [
                    'headers' => [
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ],
                    'json' => [
                        'clientKey' => $this->key,
                        'taskId' => $taskId->getTaskId()
                    ]
                ]
            );

            $result = $response->toArray();

            if (empty($result['solution']['text'])) {
                return null;
            }

            return new CaptchaSolved($result['solution']['text']);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }
    }
}
