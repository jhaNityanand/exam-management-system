<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SitemapController extends Controller
{
    public function index(): View
    {
        $links = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Exams', 'url' => route('frontend.exams.index')],
            ['label' => 'Blogs', 'url' => route('frontend.blogs.index')],
            ['label' => 'News', 'url' => route('frontend.news.index')],
            ['label' => 'Questions', 'url' => route('frontend.questions.index')],
            ['label' => 'Categories', 'url' => route('frontend.categories.index')],
            ['label' => 'Authors', 'url' => route('frontend.authors.index')],
            ['label' => 'FAQs', 'url' => route('frontend.faqs.index')],
            ['label' => 'Search', 'url' => route('frontend.search')],
            ['label' => 'About Us', 'url' => url('/about-us')],
            ['label' => 'Contact Us', 'url' => url('/contact-us')],
            ['label' => 'Privacy Policy', 'url' => url('/privacy-policy')],
            ['label' => 'Terms & Conditions', 'url' => url('/terms-and-conditions')],
        ];

        return view('frontend.sitemap.index', [
            'links' => $links,
        ]);
    }
}
