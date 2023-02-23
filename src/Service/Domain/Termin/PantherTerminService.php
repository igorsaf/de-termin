<?php

namespace DeTermin\Service\Domain\Termin;

use DateInterval;
use DateTimeImmutable;
use DeTermin\Domain\Captcha\CaptchaImage;
use DeTermin\Domain\Captcha\CaptchaSolved;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client;

final class PantherTerminService implements TerminServiceInterface
{
    private const NO_SLOTS_TITLE = 'Unfortunately, there are no appointments available at this time. New appointments will be made available for booking at regular intervals.';

    public function __construct(
        private readonly Client $client,
        private readonly string $terminUrl,
        private readonly string $terminLocationCode,
        private readonly int $terminRealmId,
        private readonly int $terminCategoryId,
    ) {}

    public function loadTerminSlotsForDatesInterval(DateTimeImmutable $startDate, DateInterval $interval): void
    {
        $queryUrl = $this->terminUrl . '?' . http_build_query([
            'locationCode' => $this->terminLocationCode,
            'realmId' => $this->terminRealmId,
            'categoryId' => $this->terminCategoryId,
            'date' => $startDate->add($interval)->format('d.m.Y')
        ]);

        $this->client->request('GET', $queryUrl);
    }

    public function isTerminPageHasCaptcha(): bool
    {
        try {
            $this->client->findElement(WebDriverBy::id('appointment_captcha_month'));
            return true;
        } catch (WebDriverException) {
            return false;
        }
    }

    public function extractTerminCaptcha(): CaptchaImage
    {
        $captchaImageElement = $this->client->findElement(WebDriverBy::cssSelector('#appointment_captcha_month captcha div'));

        return new CaptchaImage(
            $this->extractBase64FromStyleAttribute(
                $captchaImageElement->getAttribute('style')
            )
        );
    }

    public function submitSolvedCaptcha(CaptchaSolved $captchaSolved)
    {
        $captchaInput = $this->client->findElement(WebDriverBy::id('appointment_captcha_month_captchaText'));

        $captchaInput->sendKeys($captchaSolved->getCode());

        $this->client->submitForm('appointment_captcha_month_appointment_showMonth');
    }

    public function saveTerminSlotsPicture(string $picturePath): bool
    {
        $this->client->findElement(WebDriverBy::cssSelector('h2 a:last-of-type'))->click();

        try {
            $this->client->waitForInvisibility('span#question');

            $text = $this->client->findElement(WebDriverBy::cssSelector('h2:first-of-type'))->getText();

            if ($text !== self::NO_SLOTS_TITLE) {
                $this->client->takeScreenshot($picturePath);
                return true;
            }

            return false;
        } catch (WebDriverException) {
            $this->client->takeScreenshot($picturePath);
            return true;
        }
    }

    private function extractBase64FromStyleAttribute(string $imageBase64): string {
        $first = explode('url(\'data:image/jpg;base64,', $imageBase64);
        $second = explode('\')', $first[1]);

        return $second[0];
    }
}
