<?php

namespace DeTermin\Domain\Captcha;

use DeTermin\Domain\Captcha\Exceptions\CaptchaNotSolvedException;

interface CaptchaServiceInterface
{

    /**
     * @throws CaptchaNotSolvedException
     */
    public function solveCaptcha(CaptchaImage $captchaImage): ?CaptchaSolved;

}
