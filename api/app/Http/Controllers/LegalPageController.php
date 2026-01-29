<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class LegalPageController extends Controller
{
    /**
     * Afficher une page légale
     */
    public function show(string $slug)
    {
        $page = LegalPage::findBySlug($slug);
        
        if (!$page) {
            abort(404);
        }
        
        $settings = SiteSetting::getAll();
        $legalPages = LegalPage::getPublishedPages();
        
        return view('legal.show', compact('page', 'settings', 'legalPages'));
    }
    
    /**
     * Page FAQ avec format spécial
     */
    public function faq()
    {
        $page = LegalPage::findBySlug(LegalPage::SLUG_FAQ);
        
        if (!$page) {
            abort(404);
        }
        
        $settings = SiteSetting::getAll();
        $legalPages = LegalPage::getPublishedPages();
        
        return view('legal.faq', compact('page', 'settings', 'legalPages'));
    }
}
