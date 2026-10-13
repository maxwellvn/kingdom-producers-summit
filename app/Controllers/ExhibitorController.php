<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Exhibitor;
use App\Services\ExhibitorMail;
use PDOException;

/** Apply to exhibit at the active event. Free; the team approves each application in admin. */
final class ExhibitorController extends Controller
{
    private const MIN_SECONDS_TO_FILL = 4;

    public function show(): Response
    {
        return $this->view('exhibit/index', [
            'title'       => 'Exhibit at the summit — ' . config('app.summit.edition'),
            'bodyClass'   => 'page-register page-exhibit',
            'description' => 'Apply for a free stand to show your products, services or work at the Kingdom Producers Summit.',
            'summit'      => config('app.summit'),
            'categories'  => Exhibitor::CATEGORIES,
        ]);
    }

    public function store(Request $request): Response
    {
        // A filled honeypot or an instant submit is a script; it gets an ordinary-looking error.
        $opened = (int) $request->str('opened_at');
        if ($request->str('website_url') !== '' || ($opened > 0 && time() - $opened < self::MIN_SECONDS_TO_FILL)) {
            return $this->back($request, ['email' => 'Please check your details and try again.'], $request->all());
        }

        $data = [
            'first_name'  => mb_substr(trim($request->str('first_name')), 0, 80),
            'last_name'   => mb_substr(trim($request->str('last_name')), 0, 80),
            'email'       => mb_strtolower(mb_substr(trim($request->str('email')), 0, 190)),
            'phone'       => mb_substr(trim($request->str('phone')), 0, 40) ?: null,
            'kingschat'   => mb_substr(ltrim(trim($request->str('kingschat')), '@'), 0, 80) ?: null,
            'business'    => mb_substr(trim($request->str('business')), 0, 160),
            'category'    => $request->str('category'),
            'description' => mb_substr(trim($request->str('description')), 0, 1500),
            'website'     => mb_substr(trim($request->str('website')), 0, 255) ?: null,
            'staff'       => (int) $request->str('staff', '1'),
            'needs_power' => $request->input('needs_power') === '1' ? 1 : 0,
            'notes'       => mb_substr(trim($request->str('notes')), 0, 600) ?: null,
        ];

        $errors = [];
        if (mb_strlen($data['first_name']) < 2) { $errors['first_name'] = 'Tell us your first name.'; }
        if (mb_strlen($data['last_name']) < 2) { $errors['last_name'] = 'Tell us your surname.'; }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'That email address does not look right.'; }
        if (mb_strlen($data['business']) < 2) { $errors['business'] = 'Tell us the name of your business or project.'; }
        if (!in_array($data['category'], Exhibitor::CATEGORIES, true)) { $errors['category'] = 'Choose the closest category.'; }
        if (mb_strlen($data['description']) < 20) { $errors['description'] = 'Tell us a little more about what you will show (at least a sentence).'; }
        if ($data['website'] !== null && !preg_match('#^(https?://)?[^\s/$.?\#].[^\s]*$#i', $data['website'])) { $errors['website'] = 'That link does not look right.'; }
        if ($data['staff'] < 1 || $data['staff'] > 6) { $errors['staff'] = 'Between 1 and 6 people per stand.'; }
        if ($request->input('consent') !== '1') { $errors['consent'] = 'Please agree so we can contact you about your application.'; }
        if (!$errors && Exhibitor::emailExists($data['email'])) {
            $errors['email'] = 'We already have an application from this email for this event. Reply to our email if anything has changed.';
        }
        if ($errors) {
            return $this->back($request, $errors, $request->all());
        }

        try {
            $id = Exhibitor::create($data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->back($request, ['email' => 'We already have an application from this email for this event.'], $request->all());
            }
            throw $e;
        }

        $saved = ['id' => $id] + $data;
        ExhibitorMail::received($saved);
        ExhibitorMail::notifyTeam($saved);
        Session::put('exhibitor_applied', $data['business']);

        return $this->redirect('/exhibit/thanks');
    }

    public function thanks(): Response
    {
        $business = (string) Session::get('exhibitor_applied', '');
        if ($business === '') {
            return $this->redirect('/exhibit');
        }

        return $this->view('exhibit/thanks', [
            'title'     => 'Application received — ' . config('app.name'),
            'bodyClass' => 'page-register page-exhibit',
            'noIndex'   => true,
            'business'  => $business,
        ]);
    }
}
