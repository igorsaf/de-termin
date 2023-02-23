<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services
        ->load('DeTermin\\', __DIR__ . '/../src/*')
    ;

    $services
        ->load('DeTermin\\Controller\\Cli\\', __DIR__ . '/../src/Controller/Cli/**/*CliCommand.php')
        ->tag('console.command')
    ;

    $services->alias(
        \DeTermin\Domain\Captcha\CaptchaServiceInterface::class,
        \DeTermin\Service\Domain\Captcha\AntiCaptchaService::class
    );

    $services->alias(
        \DeTermin\Service\Domain\Termin\TerminServiceInterface::class,
        \DeTermin\Service\Domain\Termin\PantherTerminService::class,
    );

    $services->alias(
        \DeTermin\Domain\Notification\NotificationServiceInterface::class,
        \DeTermin\Service\Domain\Notification\TelegramNotificationService::class
    );

    $services
        ->set(\DeTermin\Service\Domain\Captcha\AntiCaptchaService::class)
        ->arg('$baseUri', '%env(ANTICAPTCHA_API_BASE_URI)%')
        ->arg('$key', '%env(ANTICAPTCHA_API_KEY)%')
    ;

    $services
        ->set('panther.client')
        ->class(\Symfony\Component\Panther\Client::class)
        ->factory([\Symfony\Component\Panther\Client::class, 'createChromeClient'])
        ->args(['%env(CHROME_DRIVER_BINARY_PATH)%'])
    ;

    $services
        ->set(\DeTermin\Service\Domain\Termin\PantherTerminService::class)
        ->arg('$client', service('panther.client'))
        ->arg('$terminUrl', '%env(DE_TERMIN_URL)%')
        ->arg('$terminLocationCode', '%env(DE_TERMIN_LOCATION_CODE)%')
        ->arg('$terminRealmId', '%env(DE_TERMIN_REALM_ID)%')
        ->arg('$terminCategoryId', '%env(DE_TERMIN_CATEGORY_ID)%')
    ;

    $services
        ->set(\DeTermin\Service\Domain\Notification\TelegramNotificationService::class)
        ->arg('$baseUri', '%env(TELEGRAM_NOTIFICATION_API_BASE_URI)%')
        ->arg('$botId', '%env(TELEGRAM_NOTIFICATION_BOT_ID)%')
        ->arg('$botToken', '%env(TELEGRAM_NOTIFICATION_BOT_TOKEN)%')
        ->arg('$chatId', '%env(TELEGRAM_NOTIFICATION_CHAT_ID)%')
    ;
};
