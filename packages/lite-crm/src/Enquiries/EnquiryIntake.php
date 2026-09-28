<?php

declare(strict_types=1);

namespace LiteCrm\Enquiries;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enums\EnquiryStatus;
use LiteCrm\Events\EnquiryCaptured;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Document;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Lookup;

/**
 * Validates and stores an enquiry, whatever its source (website form, HTTP
 * endpoint, or code). Spam is stored with the Spam status for review and
 * triggers no e-mails.
 */
class EnquiryIntake
{
    /** Fields stored in their own columns; everything else goes into "payload". */
    public const STANDARD_FIELDS = ['name', 'company', 'email', 'phone', 'country', 'city', 'message'];

    public function __construct(protected SpamGuard $spamGuard) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function capture(array $data, string $type, ?string $sourceUrl = null, ?Submission $submission = null): Enquiry
    {
        $submission ??= Submission::trusted();

        $validated = $this->validate($data, $type, $sourceUrl, $submission);
        $spamReason = $this->spamGuard->reason($submission);

        $enquiry = DB::transaction(function () use ($validated, $type, $sourceUrl, $submission, $spamReason): Enquiry {
            /** @var class-string<Enquiry> $model */
            $model = LiteCrm::model(Enquiry::class);

            $payload = $validated['payload'];

            if ($spamReason !== null && $submission->attachments !== []) {
                // Files sent with spam are not kept; the count is recorded for review.
                $payload['_discarded_attachments'] = count($submission->attachments);
            }

            /** @var Enquiry $enquiry */
            $enquiry = new $model;
            $enquiry->fill(Arr::only($validated, self::STANDARD_FIELDS));
            $enquiry->forceFill([
                'type_id' => $this->typeId($type),
                'status' => $spamReason === null ? EnquiryStatus::New : EnquiryStatus::Spam,
                'spam_reason' => $spamReason,
                'payload' => $payload === [] ? null : $payload,
                'source_url' => $sourceUrl,
                'channel' => $submission->channel,
                'consent_given' => $submission->consent,
                'consent_at' => $submission->consent ? Date::now() : null,
                'ip_hash' => $submission->ip !== null ? hash_hmac('sha256', $submission->ip, (string) config('app.key')) : null,
                'user_agent' => $submission->userAgent !== null ? Str::limit($submission->userAgent, 490, '') : null,
            ]);
            $enquiry->save();

            if ($spamReason === null) {
                foreach ($submission->attachments as $file) {
                    $this->storeAttachment($enquiry, $file);
                }
            }

            return $enquiry;
        });

        if ($spamReason === null) {
            EnquiryCaptured::dispatch($enquiry);
        }

        return $enquiry;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> The standard fields, plus "payload" for the rest.
     *
     * @throws ValidationException
     */
    protected function validate(array $data, string $type, ?string $sourceUrl, Submission $submission): array
    {
        $payload = Arr::except($data, self::STANDARD_FIELDS);

        /** @var list<string> $mimeTypes */
        $mimeTypes = config('lite-crm.documents.accepted_mime_types', []);

        Validator::make(
            [
                ...Arr::only($data, self::STANDARD_FIELDS),
                'type' => $type,
                'source_url' => $sourceUrl,
                'payload' => $payload,
                'attachments' => $submission->attachments,
            ],
            [
                'type' => ['required', function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->typeId((string) $value) === null) {
                        $fail(__('lite-crm::enquiries.validation.unknown_type'));
                    }
                }],
                'name' => ['nullable', 'string', 'max:255'],
                'company' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'country' => ['nullable', 'string', 'max:100'],
                'city' => ['nullable', 'string', 'max:255'],
                'message' => ['nullable', 'string', 'max:10000'],
                'source_url' => ['nullable', 'string', 'max:2048'],
                'payload' => ['array', 'max:50'],
                'payload.*' => ['nullable'],
                'attachments' => ['array', 'max:'.(int) config('lite-crm.enquiries.max_uploads', 5)],
                'attachments.*' => [
                    'file',
                    'mimetypes:'.implode(',', $mimeTypes),
                    'max:'.(int) config('lite-crm.documents.max_size_kb', 10240),
                ],
            ],
        )->validate();

        $validated = [];

        foreach (self::STANDARD_FIELDS as $field) {
            $value = $data[$field] ?? null;
            $validated[$field] = is_string($value) ? trim($value) : $value;
        }

        if (is_string($validated['email'])) {
            $validated['email'] = Str::lower($validated['email']);
        }

        $validated['payload'] = $this->cleanPayload($payload);

        return $validated;
    }

    /**
     * Keeps extra form answers as plain strings (or lists of strings).
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<string, string|list<string>>
     */
    protected function cleanPayload(array $payload): array
    {
        $clean = [];

        foreach ($payload as $key => $value) {
            $key = Str::limit(Str::snake((string) $key), 60, '');

            if ($key === '' || str_starts_with($key, '_') || $value === null || $value === '') {
                continue;
            }

            $clean[$key] = is_array($value)
                ? array_values(array_map(fn (mixed $item): string => Str::limit(is_scalar($item) ? (string) $item : '', 1000, ''), $value))
                : Str::limit(is_scalar($value) ? (string) $value : '', 5000, '');
        }

        return $clean;
    }

    protected function typeId(string $type): ?int
    {
        /** @var int|null $id */
        $id = LiteCrm::model(Lookup::class)::query()
            ->where('type', 'enquiry_type')
            ->where('key', $type)
            ->where('is_active', true)
            ->value('id');

        return $id;
    }

    protected function storeAttachment(Enquiry $enquiry, UploadedFile $file): Document
    {
        $disk = (string) config('lite-crm.documents.disk', 'local');
        $path = $file->store((string) config('lite-crm.documents.directory', 'crm/documents'), $disk);

        if ($path === false) {
            throw new \RuntimeException('The attachment could not be stored.');
        }

        /** @var Document $document */
        $document = $enquiry->documents()->create([
            'title' => Str::limit($file->getClientOriginalName(), 250, ''),
            'file_path' => $path,
            'file_name' => Str::limit($file->getClientOriginalName(), 250, ''),
        ]);

        return $document;
    }
}
