<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\LiteCrmPlugin;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Pipeline;
use LiteCrm\Models\PipelineStage;
use LiteCrm\Support\Visibility;
use Livewire\Attributes\Url;

/**
 * Kanban board per pipeline: drag a card to another stage (or use its "Move to"
 * menu, which works with the keyboard). Every move is authorised, logged as an
 * activity and fires OpportunityStageChanged. Moving to a "lost" stage asks for
 * the lost reason first.
 */
class OpportunityBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static ?int $navigationSort = 16;

    protected static ?string $slug = 'opportunity-board';

    protected string $view = 'lite-crm::filament.pages.opportunity-board';

    /** Closed (won/lost) opportunities older than this many days are left off the board. */
    public const CLOSED_DAYS = 90;

    #[Url]
    public ?int $pipeline = null;

    public static function canAccess(): bool
    {
        return LiteCrm::isModuleEnabled('opportunities')
            && Gate::allows('viewAny', LiteCrm::model(Opportunity::class));
    }

    public static function getNavigationGroup(): ?string
    {
        return LiteCrmPlugin::recordNavigationGroup('board');
    }

    public static function getNavigationSort(): ?int
    {
        return LiteCrmPlugin::navigationPlacement('board')['sort'] ?? parent::getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return __('lite-crm::opportunities.board.navigation');
    }

    public function getTitle(): string
    {
        return __('lite-crm::opportunities.board.title');
    }

    public function mount(): void
    {
        $pipelines = OpportunityResource::pipelineOptions();

        if ($this->pipeline === null || ! array_key_exists($this->pipeline, $pipelines)) {
            $this->pipeline = array_key_first($pipelines);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getPipelineOptions(): array
    {
        return OpportunityResource::pipelineOptions();
    }

    /**
     * The stages of the chosen pipeline, each with its cards and totals.
     *
     * @return list<array{stage: PipelineStage, cards: Collection<int, Opportunity>, totals: array<string, float>}>
     */
    public function getColumns(): array
    {
        if ($this->pipeline === null || ! array_key_exists($this->pipeline, $this->getPipelineOptions())) {
            return [];
        }

        /** @var Collection<int, PipelineStage> $stages */
        $stages = LiteCrm::model(PipelineStage::class)::query()->where('pipeline_id', $this->pipeline)->orderBy('sort')->get();

        /** @var Collection<int, Opportunity> $opportunities */
        $opportunities = Visibility::apply(LiteCrm::model(Opportunity::class)::query(), Filament::auth()->user())
            ->with(['organisation', 'owner'])
            ->where('pipeline_id', $this->pipeline)
            ->where(fn ($query) => $query->whereNull('closed_at')->orWhere('closed_at', '>=', now()->subDays(self::CLOSED_DAYS)))
            ->orderBy('sort')
            ->orderBy('expected_close_date')
            ->get();

        $columns = [];

        foreach ($stages as $stage) {
            $cards = $opportunities->where('stage_id', $stage->getKey())->values();
            $totals = [];

            foreach ($cards as $card) {
                if ($card->value !== null) {
                    $currency = $card->currency ?? LiteCrm::baseCurrency();
                    $totals[$currency] = ($totals[$currency] ?? 0) + (float) $card->value;
                }
            }

            $columns[] = ['stage' => $stage, 'cards' => $cards, 'totals' => $totals];
        }

        return $columns;
    }

    public function updatedPipeline(): void
    {
        if (! array_key_exists((int) $this->pipeline, $this->getPipelineOptions())) {
            $this->pipeline = array_key_first($this->getPipelineOptions());
        }
    }

    /**
     * Called by drag-and-drop and by the "Move to" menu.
     */
    public function moveOpportunity(int $opportunityId, int $stageId): void
    {
        [$opportunity, $stage] = $this->resolveMove($opportunityId, $stageId);

        if ($opportunity === null || $stage === null || $opportunity->stage_id === $stage->getKey()) {
            return;
        }

        if ($stage->is_lost) {
            $this->mountAction('markLost', ['opportunity' => $opportunity->getKey(), 'stage' => $stage->getKey()]);

            return;
        }

        $opportunity->update(['stage_id' => $stage->getKey()]);

        Notification::make()
            ->title(__('lite-crm::opportunities.board.moved', ['name' => $opportunity->name, 'stage' => $stage->name]))
            ->success()
            ->send();
    }

    public function markLostAction(): Action
    {
        return Action::make('markLost')
            ->modalHeading(__('lite-crm::opportunities.actions.mark_lost'))
            ->schema([Fields::lookup('lost_reason_id', 'lost_reason', __('lite-crm::opportunities.fields.lost_reason'))->required()])
            ->action(function (array $data, array $arguments): void {
                [$opportunity, $stage] = $this->resolveMove((int) ($arguments['opportunity'] ?? 0), (int) ($arguments['stage'] ?? 0));

                if ($opportunity === null || $stage === null || ! $stage->is_lost) {
                    return;
                }

                $opportunity->update(['stage_id' => $stage->getKey(), 'lost_reason_id' => $data['lost_reason_id']]);
            });
    }

    /**
     * The opportunity and target stage, if the user may make this move.
     *
     * @return array{0: Opportunity|null, 1: PipelineStage|null}
     */
    protected function resolveMove(int $opportunityId, int $stageId): array
    {
        /** @var Opportunity|null $opportunity */
        $opportunity = Visibility::apply(LiteCrm::model(Opportunity::class)::query(), Filament::auth()->user())->find($opportunityId);

        if ($opportunity === null || ! Gate::allows('update', $opportunity)) {
            Notification::make()->title(__('lite-crm::opportunities.board.not_allowed'))->danger()->send();

            return [null, null];
        }

        /** @var PipelineStage|null $stage */
        $stage = LiteCrm::model(PipelineStage::class)::query()
            ->where('pipeline_id', $opportunity->pipeline_id)
            ->find($stageId);

        return [$opportunity, $stage];
    }

    public function opportunityUrl(Opportunity $opportunity): string
    {
        return OpportunityResource::getUrl('view', ['record' => $opportunity]);
    }

    /**
     * Pipelines exist but none is visible: explains the empty board.
     */
    public function hasPipelines(): bool
    {
        return LiteCrm::model(Pipeline::class)::query()->exists() && $this->getPipelineOptions() !== [];
    }
}
