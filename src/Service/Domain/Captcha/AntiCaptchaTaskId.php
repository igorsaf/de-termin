<?php

namespace DeTermin\Service\Domain\Captcha;

final class AntiCaptchaTaskId
{
    public function __construct(
        private readonly int $taskId
    )
    {
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }


}
