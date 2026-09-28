<?php

declare(strict_types=1);

namespace LiteCrm\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LiteCrm\Enquiries\ApiToken;
use LiteCrm\Enquiries\EnquiryIntake;
use LiteCrm\Enquiries\SpamGuard;
use LiteCrm\Enquiries\Submission;
use LiteCrm\LiteCrm;

/**
 * POST /crm-api/enquiries — lets other websites post enquiries.
 *
 * Off unless lite-crm.enquiry_api.enabled is true. Requires the token from
 * CRM › Settings (Authorization: Bearer ...). Applies the same validation,
 * spam checks and per-IP limit as the website forms. Senders can forward the
 * form's honeypot ("_honeypot") and the time the form was shown ("_started_at",
 * a Unix timestamp). Spam gets the same response as a genuine enquiry.
 */
class EnquiryApiController
{
    /** Request keys that are not enquiry fields. */
    public const RESERVED = ['type', 'source_url', 'consent', '_honeypot', '_started_at'];

    public function __invoke(Request $request, ApiToken $token, SpamGuard $spamGuard, EnquiryIntake $intake): JsonResponse
    {
        abort_unless(config('lite-crm.enquiry_api.enabled') && LiteCrm::isModuleEnabled('enquiries'), 404);

        if (! $token->verify($request->bearerToken())) {
            return response()->json(['message' => __('lite-crm::enquiries.api.unauthorised')], 401);
        }

        if ($spamGuard->tooManyAttempts($request->ip())) {
            return response()->json(['message' => __('lite-crm::enquiries.form.throttled')], 429);
        }

        $startedAt = $request->input('_started_at');

        try {
            $enquiry = $intake->capture(
                data: $request->except(self::RESERVED),
                type: (string) $request->input('type', ''),
                sourceUrl: $request->filled('source_url') ? (string) $request->input('source_url') : null,
                submission: Submission::fromRequest(
                    $request,
                    Submission::API,
                    honeypot: $request->filled('_honeypot') ? (string) $request->input('_honeypot') : null,
                    startedAt: is_numeric($startedAt) ? (int) $startedAt : null,
                    consent: $request->boolean('consent'),
                ),
            );
        } catch (ValidationException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
        }

        return response()->json(['status' => 'received', 'reference' => $enquiry->getKey()], 202);
    }
}
