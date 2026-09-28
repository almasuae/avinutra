<?php

declare(strict_types=1);

namespace LiteCrm\Database\Seeders;

use Illuminate\Database\Seeder;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Pipeline;

/**
 * Neutral starting lists that suit any business. Presets add industry lists.
 * Idempotent: existing entries (and Admin edits to them) are left alone.
 */
class DefaultLookupsSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    public const LOOKUPS = [
        'organisation_type' => ['customer', 'supplier', 'partner', 'service_provider', 'other'],
        'organisation_status' => ['prospect', 'active', 'inactive'],
        'activity_type' => ['call', 'meeting', 'email', 'message', 'visit', 'note'],
        'document_type' => ['contract', 'certificate', 'specification', 'company_profile', 'other'],
        'lost_reason' => ['price', 'timing', 'competitor', 'requirements', 'no_response', 'other'],
        'enquiry_type' => ['general', 'quotation', 'partnership'],
    ];

    /** @var array<string, array{probability: int, is_won?: bool, is_lost?: bool}> */
    public const SALES_STAGES = [
        'new' => ['probability' => 10],
        'qualified' => ['probability' => 25],
        'proposal' => ['probability' => 50],
        'negotiation' => ['probability' => 75],
        'won' => ['probability' => 100, 'is_won' => true],
        'lost' => ['probability' => 0, 'is_lost' => true],
    ];

    public function run(): void
    {
        /** @var class-string<Lookup> $lookup */
        $lookup = LiteCrm::model(Lookup::class);

        foreach (self::LOOKUPS as $type => $keys) {
            foreach ($keys as $sort => $key) {
                $lookup::withTrashed()->firstOrCreate(
                    ['type' => $type, 'key' => $key],
                    ['label' => __("lite-crm::defaults.lookups.{$type}.{$key}"), 'sort' => ($sort + 1) * 10],
                );
            }
        }

        /** @var class-string<Pipeline> $pipelineModel */
        $pipelineModel = LiteCrm::model(Pipeline::class);

        $pipeline = $pipelineModel::withTrashed()->firstOrCreate(
            ['key' => 'sales'],
            ['name' => __('lite-crm::defaults.pipelines.sales.name'), 'sort' => 10],
        );

        if ($pipeline->wasRecentlyCreated) {
            $sort = 0;

            foreach (self::SALES_STAGES as $key => $stage) {
                $pipeline->stages()->create([
                    'key' => $key,
                    'name' => __("lite-crm::defaults.pipelines.sales.stages.{$key}"),
                    'probability' => $stage['probability'],
                    'is_won' => $stage['is_won'] ?? false,
                    'is_lost' => $stage['is_lost'] ?? false,
                    'sort' => $sort += 10,
                ]);
            }
        }
    }
}
