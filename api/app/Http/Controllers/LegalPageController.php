<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Models\SiteSetting;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class LegalPageController extends Controller
{
    /**
     * Display a legal page by its slug.
     */
    public function show(string $slug): Response|\Illuminate\Http\RedirectResponse
    {
        $page = LegalPage::findBySlug($slug);

        if (! $page) {
            abort(404);
        }

        $settings = SiteSetting::getAll();
        $legalPages = LegalPage::getPublishedPages();

        return response()->view('legal.show', compact('page', 'settings', 'legalPages'));
    }

    /**
     * Display the FAQ page.
     */
    public function faq(): Response
    {
        $page = LegalPage::findBySlug(LegalPage::SLUG_FAQ);

        if (! $page) {
            abort(404);
        }

        $settings = SiteSetting::getAll();
        $legalPages = LegalPage::getPublishedPages();

        return response()->view('legal.faq', compact('page', 'settings', 'legalPages'));
    }
}
