<?php
declare(strict_types=1);

namespace DeTermin\Domain\Captcha;

final class CaptchaSolved
{
    public function __construct(
        private readonly string $code
    )
    {
    }

    public function getCode()
    {
        return $this->code;
    }
}
