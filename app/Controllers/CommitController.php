<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Commitment;

/** The Commitment card: four promises, a name, prayed over after the session. */
final class CommitController extends Controller
{
    public function show(): Response
    {
        return $this->view('commit/index', [
            'title'       => 'The Commitment — ' . config('app.name'),
            'bodyClass'   => 'page-commit',
            'description' => 'Make your four producer commitments.',
            'items'       => Commitment::ITEMS,
            'titles'      => Commitment::TITLES,
        ]);
    }

    public function store(Request $request): Response
    {
        if ($request->str('website') !== '') { // honeypot
            return $this->redirect('/commitment/thanks');
        }
        $title = $request->str('title');
        $first = mb_substr($request->str('first_name'), 0, 80);
        $last = mb_substr($request->str('last_name'), 0, 80);
        $email = mb_strtolower(mb_substr($request->str('email'), 0, 190));
        $errors = [];
        if ($title !== '' && !in_array($title, Commitment::TITLES, true)) {
            $errors['title'] = 'Choose a title from the list.';
        }
        if (mb_strlen($first) < 2) {
            $errors['first_name'] = 'Tell us your first name.';
        }
        if (mb_strlen($last) < 2) {
            $errors['last_name'] = 'Tell us your surname.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        $ticked = array_filter(array_keys(Commitment::ITEMS), static fn ($k) => $request->str($k) === '1');
        if ($ticked === []) {
            $errors['items'] = 'Tick at least one commitment.';
        }
        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }

        Commitment::create([
            'title'     => $title !== '' ? $title : null,
            'first_name'=> $first,
            'last_name' => $last,
            'email'     => $email,
            'kingschat' => ($k = ltrim(mb_substr($request->str('kingschat'), 0, 80), '@')) !== '' ? $k : null,
            'produce'   => (int) in_array('produce', $ticked, true),
            'records'   => (int) in_array('records', $ticked, true),
            'buy'       => (int) in_array('buy', $ticked, true),
            'teach'     => (int) in_array('teach', $ticked, true),
        ]);
        Session::flash('commit_name', $first);

        return $this->redirect('/commitment/thanks');
    }

    public function thanks(): Response
    {
        return $this->view('commit/thanks', [
            'title'     => 'Thank you — ' . config('app.name'),
            'bodyClass' => 'page-commit',
            'noIndex'   => true,
            'name'      => (string) Session::get('commit_name', ''),
            'items'     => Commitment::ITEMS,
        ]);
    }
}
