<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CalculatorDefault;
use App\Services\Calculators\MethionineValueCalculator;
use Illuminate\Database\Seeder;

/**
 * Tool 1 value-factor presets (owner's decision of 29 Sep 2026), editable in
 * CRM › Website › Calculator defaults. Seeded unapproved, so the website labels
 * them "Indicative default" until the nutrition panel approves them (gap #14).
 * The landed-cost tool has no defaults: its inputs start blank.
 */
class CalculatorDefaultSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MethionineValueCalculator::PRESETS as $key => $preset) {
            if ($preset['factor'] === null) {
                continue;
            }

            CalculatorDefault::query()->firstOrCreate(
                ['tool' => MethionineValueCalculator::TOOL, 'key' => $key],
                [
                    'label' => $preset['label'].' — value factor (kg DL-Met 99% per kg, product basis)',
                    'value' => $preset['factor'],
                    'unit' => 'factor',
                    'source' => $preset['note'],
                    'source_date' => '2026-09-29',
                ],
            );
        }
    }
}
