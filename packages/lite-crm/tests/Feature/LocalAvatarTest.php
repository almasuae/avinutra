<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use LiteCrm\Filament\LocalAvatarProvider;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

it('draws initials avatars locally instead of calling an external service', function (): void {
    $this->seedCrm();
    Filament::setCurrentPanel(Filament::getPanel('crm'));
    $user = $this->crmUser(['admin']);
    $user->forceFill(['name' => 'Ayesha Khan'])->save();

    $avatar = (new LocalAvatarProvider)->get($user);

    expect(Filament::getPanel('crm')->getDefaultAvatarProvider())->toBe(LocalAvatarProvider::class)
        ->and($avatar)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr($avatar, strlen('data:image/svg+xml;base64,'))))->toContain('>AK<')
        ->and($avatar)->not->toContain('http');
});
