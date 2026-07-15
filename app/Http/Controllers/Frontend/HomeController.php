<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\HomePageService;
use App\Services\Frontend\SiteCmsService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected HomePageService $homePageService,
        protected SiteCmsService $siteCmsService,
    ) {}

    public function index(): View
    {
        $page = $this->homePageService->build();

        return view('frontend.home.index', [
            'page' => $page,
            'cms' => $this->siteCmsService,
        ]);
    }
}
