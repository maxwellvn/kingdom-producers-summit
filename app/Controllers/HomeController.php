<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Events;
use App\Core\Request;
use App\Core\Response;
use App\Models\Registration;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        if (Events::isChooser($request->path)) {
            return $this->choose();
        }

        return $this->view('home/index', [
            'title'     => config('app.name') . ' — ' . config('app.summit.edition'),
            'bodyClass' => 'page-home',
            'description' => 'A one-day summit and an ongoing initiative that turns consumers into producers. '
                . config('app.summit.date_day') . ' in ' . config('app.summit.city')
                . '. Attend in person, watch online, or join the Kingdom Producers initiative.',
            'summit'    => config('app.summit'),
        ]);
    }

    /** The front door while two events share one day: pick a city, land on its own site. */
    private function choose(): Response
    {
        $events = [];
        foreach (Events::SLUGS as $slug) {
            $events[$slug] = Events::summit($slug);
        }

        return $this->view('home/choose', [
            'title'       => config('app.name') . ' — Manchester and Ireland, one day',
            'bodyClass'   => 'page-choose',
            'description' => 'The Loveworld Kingdom Producers Summit runs in Manchester and across Ireland on the same day. '
                . 'Choose your city to register, or to watch online.',
            'events'      => $events,
        ]);
    }

    /**
     * Counts only, both events, for whoever holds the key. No names or contact details,
     * so the link can be forwarded; regenerating or switching it off in admin kills old copies.
     */
    public function status(Request $request): Response
    {
        $key = \App\Models\Setting::get('status_share_token', '');
        if ($key === '' || !hash_equals($key, $request->str('key'))) {
            return Response::html(\App\Core\View::render('errors/404', ['title' => 'Not found']), 404);
        }

        $events = [];
        foreach (Events::SLUGS as $slug) {
            $events[$slug] = Events::using($slug, static fn (): array => [
                'summit'     => (array) config('app.summit'),
                'stats'      => Registration::stats(),
                'stages'     => Registration::byStage(),
                'attendance' => Registration::attendanceStats(),
            ]);
        }

        return $this->view('home/status', [
            'title'     => 'Registration status',
            'bodyClass' => 'page-admin page-status',
            'events'    => $events,
        ], 'layouts/admin')
            // The key sits in the URL: keep it out of Referer headers, caches and search engines.
            ->header('Refresh', '60')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
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
            'shareText' => 'The LoveWorld Kingdom Producers Summit — ' . $summit['edition'] . ', ' . $summit['date_text'] . '. Onsite in ' . $summit['place'] . ' and live online worldwide. Register at the link.',
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
