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
        ]);
    }

    public function store(Request $request): Response
    {
        if ($request->str('website') !== '') { // honeypot
            return $this->redirect('/commit/thanks');
        }
        $name = mb_substr($request->str('name'), 0, 160);
        $email = mb_strtolower(mb_substr($request->str('email'), 0, 190));
        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Tell us your name.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'That email address does not look right.';
        }
        $ticked = array_filter(array_keys(Commitment::ITEMS), static fn ($k) => $request->str($k) === '1');
        if ($ticked === []) {
            $errors['items'] = 'Tick at least one commitment.';
        }
        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }

        Commitment::create([
            'name'      => $name,
            'email'     => $email !== '' ? $email : null,
            'kingschat' => ($k = ltrim(mb_substr($request->str('kingschat'), 0, 80), '@')) !== '' ? $k : null,
            'produce'   => (int) in_array('produce', $ticked, true),
            'records'   => (int) in_array('records', $ticked, true),
            'buy'       => (int) in_array('buy', $ticked, true),
            'teach'     => (int) in_array('teach', $ticked, true),
            'what'      => ($w = mb_substr($request->str('what'), 0, 255)) !== '' ? $w : null,
        ]);
        Session::flash('commit_name', $name);

        return $this->redirect('/commit/thanks');
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
