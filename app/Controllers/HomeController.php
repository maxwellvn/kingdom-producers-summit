<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home/index', [
            'title'     => config('app.name') . ' — London Edition 2026',
            'bodyClass' => 'page-home',
            'summit'    => config('app.summit'),
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->view('home/about', [
            'title'     => 'About the Initiative — ' . config('app.name'),
            'bodyClass' => 'page-about',
            'summit'    => config('app.summit'),
        ]);
    }
}
