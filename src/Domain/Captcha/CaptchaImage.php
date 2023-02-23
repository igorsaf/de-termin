<?php
declare(strict_types=1);

namespace DeTermin\Domain\Captcha;

final class CaptchaImage
{
    public function __construct(
        private readonly string $imageBase64
    )
    {
    }

    public function getImageBase64(): string
    {
        return $this->imageBase64;
    }
}
