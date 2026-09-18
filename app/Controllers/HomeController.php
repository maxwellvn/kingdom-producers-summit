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

        return $this->view('home/index', [
            'title'     => config('app.name') . ' — ' . config('app.summit.edition'),
            'bodyClass' => 'page-home',
            'description' => 'A one-day summit and an ongoing initiative that turns consumers into producers. '
                . config('app.summit.date_day') . ' in ' . config('app.summit.city')
                . '. Attend in person, watch online, or join the Kingdom Producers initiative.',
            'summit'    => config('app.summit'),
        ]);
    }

    /** Copies the registration link and opens the device share sheet; for links in messages. */
    public function share(): Response
    {
        $summit = config('app.summit');

        return $this->view('home/share', [
            'title'     => 'Share the summit — ' . config('app.name'),
            'noIndex'   => true,
            'bodyClass' => 'page-register',
            'summit'    => $summit,
            'shareUrl'  => site_url() . '/register',
            'shareText' => 'The Inaugural LoveWorld Kingdom Producers Summit — ' . $summit['edition'] . ', ' . $summit['date_text'] . '. Onsite in London and live online worldwide. Registration is free.',
        ]);
    }

    public function privacy(Request $request): Response
    {
        return $this->view('home/privacy', [
            'title'     => 'Privacy — ' . config('app.name'),
            'description' => 'How Loveworld Consulate UK collects, uses and protects the details you give when '
                . 'registering for the Kingdom Producers Summit.',
            'bodyClass' => 'page-legal',
            'summit'    => config('app.summit'),
            'updated'   => '10 September 2026',
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->view('home/about', [
            'title'     => 'About the Initiative — ' . config('app.name'),
            'description' => 'The Kingdom Producers initiative develops 100 young people per edition into working '
                . 'producers through a 30, 60 and 90 day journey, with mentorship, networks and market access.',
            'bodyClass' => 'page-about',
            'summit'    => config('app.summit'),
        ]);
    }
}
