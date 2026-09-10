<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Registration;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $capacity = max(1, (int) config('app.summit.onsite_capacity'));

        return $this->view('home/index', [
            'title'     => config('app.name') . ' — ' . config('app.summit.edition'),
            'bodyClass' => 'page-home',
            'summit'    => config('app.summit'),
            'capacity'  => $capacity,
            'seatsLeft' => max(0, $capacity - Registration::onsiteSeatsTaken()),
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
