<?php

namespace DeTermin\Controller\Cli;

use DateInterval;
use DateTimeImmutable;
use DeTermin\Domain\Captcha\CaptchaServiceInterface;
use DeTermin\Domain\Captcha\CaptchaSolved;
use DeTermin\Domain\Notification\NotificationMessage;
use DeTermin\Domain\Notification\NotificationServiceInterface;
use DeTermin\Service\Domain\Termin\TerminServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Panther\Client;

#[AsCommand(
    name: 'check-termin-slots',
    description: 'Checking termin slots at Germany Embassy in Tbilisi ',
)]
class CheckTerminSlotsCliCommand extends Command
{
    private const CAPTCHA_SOLVING_ATTEMPTS_COUNT = 5;

    public function __construct(
        private readonly TerminServiceInterface $terminService,
        private readonly CaptchaServiceInterface $captchaService,
        private readonly NotificationServiceInterface $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateStart = new DateTimeImmutable();
        $datesInterval = new DateInterval('P3W');

        $this->terminService->loadTerminSlotsForDatesInterval($dateStart, $datesInterval);

        $screenName = $this->generateScreenName($dateStart, $datesInterval);

        if (
            $this->isTerminCaptchaSolved()
            && $this->terminService->saveTerminSlotsPicture($screenName)
        ) {
            $this->notificationService->sendNotificationWithPhoto(
                new NotificationMessage('New termin slots are available', $screenName)
            );

            unlink($screenName);

            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    private function generateScreenName(DateTimeImmutable $dateStart, DateInterval $datesInterval): string
    {
        return $dateStart->add($datesInterval)->format('Y-m-d-H-i-s') . '.png';
    }

    private function isTerminCaptchaSolved(): bool
    {
        if (!$this->terminService->isTerminPageHasCaptcha()) {
            return true;
        }

        $captchaSolvingAttempts = 0;
        $captchaSolvingResult = null;

        while ($captchaSolvingResult === null && $captchaSolvingAttempts < self::CAPTCHA_SOLVING_ATTEMPTS_COUNT) {
            $captchaSolvingAttempts++;
            $captchaImage = $this->terminService->extractTerminCaptcha();
            $captchaSolvingResult = $this->captchaService->solveCaptcha($captchaImage);
            $this->terminService->submitSolvedCaptcha($captchaSolvingResult ?? new CaptchaSolved('dummy'));
        }

        if ($this->terminService->isTerminPageHasCaptcha()) {
            return false;
        }

        return true;
    }
}
