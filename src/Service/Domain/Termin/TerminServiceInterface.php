<?php

namespace DeTermin\Service\Domain\Termin;

use DateInterval;
use DateTimeImmutable;
use DeTermin\Domain\Captcha\CaptchaImage;
use DeTermin\Domain\Captcha\CaptchaSolved;

interface TerminServiceInterface
{
    public function loadTerminSlotsForDatesInterval(DateTimeImmutable $startDate, DateInterval $interval): void;

    public function isTerminPageHasCaptcha(): bool;

    public function extractTerminCaptcha(): CaptchaImage;

    public function submitSolvedCaptcha(CaptchaSolved $captchaSolved);

    public function saveTerminSlotsPicture(string $picturePath);
}
