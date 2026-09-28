<?php

declare(strict_types=1);

namespace LiteCrm\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enquiries\EnquiryIntake;
use LiteCrm\Enquiries\SpamGuard;
use LiteCrm\Enquiries\Submission;
use LiteCrm\LiteCrm;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * <livewire:lite-crm.enquiry-form type="general" :fields="[...]" :uploads="3" privacy-url="/privacy" />
 *
 * "fields" lists the standard fields (name, company, email, phone, country,
 * city, message) and any extra questions, e.g.
 *
 *     ['name' => ['required' => true], 'email' => ['required' => true], 'message',
 *      'topic' => ['label' => 'Topic', 'type' => 'select', 'options' => ['a' => 'A']]]
 *
 * Extra answers are stored in the enquiry's payload. Types: text, email, tel,
 * textarea, select. Spam protection: a honeypot, a minimum fill time and a
 * per-IP limit; spam is stored for review but the visitor sees the usual thanks.
 */
class EnquiryForm extends Component
{
    use WithFileUploads;

    public const STANDARD_TYPES = [
        'name' => 'text',
        'company' => 'text',
        'email' => 'email',
        'phone' => 'tel',
        'country' => 'text',
        'city' => 'text',
        'message' => 'textarea',
    ];

    #[Locked]
    public string $type = 'general';

    /** @var array<string, array{label: string, type: string, required: bool, options: array<string, string>}> */
    #[Locked]
    public array $definitions = [];

    #[Locked]
    public int $maxUploads = 0;

    #[Locked]
    public ?string $privacyUrl = null;

    #[Locked]
    public ?string $submitLabel = null;

    #[Locked]
    public ?string $sourceUrl = null;

    #[Locked]
    public int $startedAt = 0;

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<int, TemporaryUploadedFile> */
    public array $attachments = [];

    public bool $consent = false;

    /** The honeypot: never shown to people. */
    public string $website = '';

    public bool $submitted = false;

    /**
     * @param  array<int|string, mixed>  $fields
     * @param  array<string, mixed>  $values  Pre-filled answers (e.g. the product a page is about).
     */
    public function mount(
        string $type = 'general',
        array $fields = ['name' => ['required' => true], 'email' => ['required' => true], 'company', 'message' => ['required' => true]],
        int $uploads = 0,
        ?string $privacyUrl = null,
        ?string $submitLabel = null,
        array $values = [],
    ): void {
        $this->type = $type;
        $this->definitions = static::normaliseFields($fields);
        $this->maxUploads = max(0, min($uploads, (int) config('lite-crm.enquiries.max_uploads', 5)));
        $this->privacyUrl = $privacyUrl;
        $this->submitLabel = $submitLabel;
        $this->sourceUrl = Str::limit(url()->current(), 2000, '');
        $this->startedAt = Date::now()->getTimestamp();

        foreach (array_keys($this->definitions) as $key) {
            $this->data[$key] = isset($values[$key]) && is_scalar($values[$key]) ? (string) $values[$key] : '';
        }
    }

    /**
     * @param  array<int|string, mixed>  $fields
     * @return array<string, array{label: string, type: string, required: bool, options: array<string, string>}>
     */
    public static function normaliseFields(array $fields): array
    {
        $definitions = [];

        foreach ($fields as $key => $definition) {
            if (is_int($key)) {
                [$key, $definition] = [(string) $definition, []];
            }

            $key = Str::snake((string) $key);
            $definition = is_array($definition) ? $definition : [];
            $type = (string) ($definition['type'] ?? self::STANDARD_TYPES[$key] ?? 'text');

            $definitions[$key] = [
                'label' => (string) ($definition['label'] ?? __("lite-crm::enquiries.fields.{$key}")),
                'type' => in_array($type, ['text', 'email', 'tel', 'textarea', 'select'], true) ? $type : 'text',
                'required' => (bool) ($definition['required'] ?? false),
                'options' => array_map('strval', (array) ($definition['options'] ?? [])),
            ];
        }

        return $definitions;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $rules = [];

        foreach ($this->definitions as $key => $definition) {
            $fieldRules = [$definition['required'] ? 'required' : 'nullable'];

            $fieldRules = [...$fieldRules, ...match ($definition['type']) {
                'email' => ['email:rfc', 'max:255'],
                'tel' => ['string', 'max:50'],
                'textarea' => ['string', 'max:5000'],
                'select' => ['in:'.implode(',', array_keys($definition['options']))],
                default => ['string', 'max:255'],
            }];

            $rules["data.{$key}"] = $fieldRules;
        }

        /** @var list<string> $mimeTypes */
        $mimeTypes = config('lite-crm.documents.accepted_mime_types', []);

        $rules['attachments'] = ['array', 'max:'.$this->maxUploads];
        $rules['attachments.*'] = ['file', 'mimetypes:'.implode(',', $mimeTypes), 'max:'.(int) config('lite-crm.documents.max_size_kb', 10240)];
        $rules['consent'] = ['accepted'];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        $attributes = ['consent' => __('lite-crm::enquiries.form.consent_attribute')];

        foreach ($this->definitions as $key => $definition) {
            $attributes["data.{$key}"] = $definition['label'];
        }

        return $attributes;
    }

    public function submit(EnquiryIntake $intake, SpamGuard $spamGuard): void
    {
        abort_unless(LiteCrm::isModuleEnabled('enquiries'), 404);

        $this->validate();

        if ($spamGuard->tooManyAttempts(request()->ip())) {
            $this->addError('form', __('lite-crm::enquiries.form.throttled'));

            return;
        }

        try {
            $intake->capture(
                data: array_filter($this->data, fn (mixed $value): bool => $value !== '' && $value !== null),
                type: $this->type,
                sourceUrl: $this->sourceUrl,
                submission: Submission::fromRequest(
                    request(),
                    Submission::FORM,
                    honeypot: $this->website,
                    startedAt: $this->startedAt,
                    consent: $this->consent,
                    attachments: array_values($this->attachments),
                ),
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError(str_starts_with($field, 'attachments') ? 'attachments' : 'form', $messages[0]);
            }

            return;
        }

        // Spam also ends here, so bots learn nothing.
        $this->submitted = true;
        $this->reset('data', 'attachments', 'consent', 'website');
    }

    public function render(): View
    {
        return view('lite-crm::livewire.enquiry-form');
    }
}
