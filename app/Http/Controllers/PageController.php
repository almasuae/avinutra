<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Content\Services;
use App\Models\Article;
use App\Models\TeamProfile;
use App\Settings\SiteSettings;
use App\Support\EnquiryForms;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Public pages that need data: home, services, team and the contact forms.
 */
class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            // "Latest Insights" is hidden while no article is published (v3 §7.1).
            'articles' => Article::query()->published()->latest('published_at')->limit(3)->get(),
        ]);
    }

    public function services(): View
    {
        return view('pages.services.index', ['services' => Services::all()]);
    }

    public function service(string $service): View
    {
        $services = Services::all();
        abort_unless(isset($services[$service]), 404);

        return view('pages.services.show', ['slug' => $service, 'service' => $services[$service], 'others' => array_diff_key($services, [$service => true])]);
    }

    public function about(): View
    {
        return view('pages.about.index', ['hasTeam' => TeamProfile::query()->public()->exists()]);
    }

    /**
     * The Team page exists only while at least one profile is public (published, consent on file).
     */
    public function team(): View
    {
        $profiles = TeamProfile::query()->public()->orderBy('sort')->orderBy('name')
            ->with(['authoredArticles' => fn ($query) => $query->published(), 'reviewedArticles' => fn ($query) => $query->published()])
            ->get();
        abort_if($profiles->isEmpty(), 404);

        return view('pages.about.team', ['profiles' => $profiles]);
    }

    /**
     * Contact with the enquiry-type picker (v3 §7.10): ?type= chooses the form.
     */
    public function contact(Request $request, SiteSettings $site): View
    {
        $withCall = filled($site->whatsapp_sales) || filled($site->whatsapp_nutrition);
        $types = EnquiryForms::contactTypes($withCall);
        $type = $request->string('type')->toString();

        return view('pages.contact', [
            'types' => $types,
            'type' => array_key_exists($type, $types) ? $type : 'general',
            // Pre-filled from product pages (the product, and the document asked for).
            'values' => array_filter([
                'product' => $request->string('product')->limit(200, '')->toString(),
                'documents_needed' => $request->string('documents_needed')->limit(100, '')->toString(),
            ]),
        ]);
    }

    public function askNutritionist(Request $request): View
    {
        $topic = $request->string('topic')->toString();

        // "Discuss your result" on a calculator pre-fills the question; nothing is sent until the visitor submits.
        return view('pages.ask-a-nutritionist', [
            'values' => array_filter([
                'topic' => array_key_exists($topic, EnquiryForms::TOPICS) ? $topic : null,
                'message' => $request->string('message')->limit(3000, '')->toString(),
            ]),
        ]);
    }
}
