<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use LiteCrm\Filament\Pages\OpportunityBoard;
use LiteCrm\Filament\Resources\Activities\ActivityResource;
use LiteCrm\Filament\Resources\Contacts\ContactResource;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;
use LiteCrm\Filament\Resources\PriceLog\PriceEntryResource;
use LiteCrm\Filament\Resources\Products\ProductResource;
use LiteCrm\Filament\Resources\Quotations\QuotationResource;
use LiteCrm\Filament\Resources\Tasks\TaskResource;
use LiteCrm\Filament\Widgets\ActivityByUserWidget;
use LiteCrm\LiteCrmPlugin;
use LiteCrm\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('crm'));
});

it('groups record screens as configured, in the configured order', function (): void {
    expect(EnquiryResource::getNavigationGroup())->toBe('Sales')
        ->and(OpportunityBoard::getNavigationGroup())->toBe('Sales')
        ->and(QuotationResource::getNavigationGroup())->toBe('Sales')
        ->and(ProductResource::getNavigationGroup())->toBe('Operations')
        ->and(PriceEntryResource::getNavigationGroup())->toBe('Operations')
        ->and(TaskResource::getNavigationGroup())->toBe('Team')
        ->and(ActivityResource::getNavigationGroup())->toBe('Team');

    $sales = [EnquiryResource::getNavigationSort(), ContactResource::getNavigationSort(), OpportunityResource::getNavigationSort(), OpportunityBoard::getNavigationSort(), QuotationResource::getNavigationSort()];
    expect($sales)->toBe(collect($sales)->sort()->values()->all())
        ->and(LiteCrmPlugin::navigationGroupLabels())->toBe(['Sales', 'Operations', 'Team']);
});

it('lets a site rename groups and move screens in config', function (): void {
    config([
        'lite-crm.navigation.groups' => [
            'pipeline' => ['label' => 'Pipeline', 'items' => ['opportunities', 'board']],
            'records' => ['label' => null, 'items' => ['organisations']],
        ],
    ]);

    expect(OpportunityResource::getNavigationGroup())->toBe('Pipeline')
        ->and(OpportunityBoard::getNavigationGroup())->toBe('Pipeline')
        // No label and no translation: the key, made readable.
        ->and(LiteCrmPlugin::navigationGroupLabel('records'))->toBe('Records')
        // Not listed: the plugin's default group.
        ->and(EnquiryResource::getNavigationGroup())->toBe(LiteCrmPlugin::recordNavigationGroup());
});

it('shows whole numbers only on the activities chart', function (): void {
    $options = (fn (): array => $this->getOptions())->call(new ActivityByUserWidget);

    expect($options['scales']['y']['ticks']['precision'])->toBe(0)
        ->and($options['scales']['y']['beginAtZero'])->toBeTrue();
});
