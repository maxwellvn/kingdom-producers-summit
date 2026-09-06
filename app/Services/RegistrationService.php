<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Core\Validator;
use App\Models\Registration;

final class RegistrationService
{
    /**
     * Validate a submission. Returns [errors, cleanData].
     * Rules differ per participation path.
     *
     * @return array{0: array<string,string>, 1: array<string,mixed>}
     */
    public function validate(Request $request): array
    {
        $participation = $request->str('participation');

        $rules = [
            'participation' => 'required|in:' . implode(',', Registration::PARTICIPATION),
            'title'         => 'max:20',
            'first_name'    => 'required|min:2|max:80',
            'last_name'     => 'required|min:2|max:80',
            'email'         => 'required|email|max:190',
            'phone'         => 'max:30|phone',
            'country'       => 'required|min:2|max:80',
            'city'          => 'max:80',
            'age_band'      => 'required|in:' . implode(',', Registration::AGE_BANDS),
            'church_group'  => 'max:120',
            'zone'          => 'max:120',
            'organisation'  => 'max:120',
            'role_title'    => 'max:120',
            'field'         => 'required|in:' . implode(',', Registration::FIELDS),
            'producer_stage'=> 'required|in:' . implode(',', Registration::STAGES),
            'what_to_produce'=> 'max:1200',
            'interests'     => 'array|max_items:8|in:' . implode(',', array_keys(Registration::INTERESTS)),
            'hear_about'    => 'in:' . implode(',', array_keys(Registration::HEAR_ABOUT)),
            'consent_terms' => 'required|accepted',
        ];

        if ($participation === 'onsite') {
            $rules['phone'] = 'required|max:30|phone';
            $rules['onsite_days'] = 'required|array|max_items:3|in:day1,day2,day3';
            $rules['dietary'] = 'max:160';
            $rules['accessibility'] = 'max:255';
            $rules['emergency_contact'] = 'max:160';
        }

        if ($participation === 'initiative') {
            $rules['contribute_as'] = 'array|max_items:6|in:' . implode(',', array_keys(Registration::CONTRIBUTE));
            $rules['portal_interest'] = 'max:1200';
        }

        $labels = [
            'participation'  => 'Participation',
            'first_name'     => 'First name',
            'last_name'      => 'Surname',
            'email'          => 'Email address',
            'phone'          => 'Phone number',
            'age_band'       => 'Age group',
            'field'          => 'Field',
            'producer_stage' => 'Producer stage',
            'onsite_days'    => 'Days attending',
            'consent_terms'  => 'the privacy notice',
            'what_to_produce'=> 'Your answer',
        ];

        $data = $request->all();
        $validator = Validator::make($data, $rules, $labels);
        $errors = $validator->passes() ? [] : $validator->errors();

        $email = mb_strtolower($request->str('email'));
        if (!isset($errors['email']) && $email !== '') {
            $duplicateMessage = self::duplicateMessage($email);
            if ($duplicateMessage !== null) {
                $errors['email'] = $duplicateMessage;
            }
        }

        if ($errors) {
            return [$errors, []];
        }

        $clean = [
            'reference'         => $this->generateReference(),
            'participation'     => $participation,
            'title'             => $this->nullable($request->str('title')),
            'first_name'        => $request->str('first_name'),
            'last_name'         => $request->str('last_name'),
            'email'             => $email,
            'phone'             => $this->nullable($request->str('phone')),
            'country'           => $request->str('country'),
            'city'              => $this->nullable($request->str('city')),
            'age_band'          => $request->str('age_band'),
            'church_group'      => $this->nullable($request->str('church_group')),
            'zone'              => $this->nullable($request->str('zone')),
            'organisation'      => $this->nullable($request->str('organisation')),
            'role_title'        => $this->nullable($request->str('role_title')),
            'field'             => $request->str('field'),
            'producer_stage'    => $request->str('producer_stage'),
            'what_to_produce'   => $this->nullable($request->str('what_to_produce')),
            'interests'         => $this->json($request->list('interests')),
            'hear_about'        => $this->nullable($request->str('hear_about')),
            'onsite_days'       => $participation === 'onsite' ? $this->json($request->list('onsite_days')) : null,
            'dietary'           => $participation === 'onsite' ? $this->nullable($request->str('dietary')) : null,
            'accessibility'     => $participation === 'onsite' ? $this->nullable($request->str('accessibility')) : null,
            'needs_letter'      => $participation === 'onsite' && $request->str('needs_letter') === '1' ? 1 : 0,
            'emergency_contact' => $participation === 'onsite' ? $this->nullable($request->str('emergency_contact')) : null,
            'wants_updates'     => $participation === 'onsite' ? 1 : ($request->str('wants_updates', '1') === '1' ? 1 : 0),
            'wants_portal'      => $participation === 'initiative' ? 1 : ($request->str('wants_portal') === '1' ? 1 : 0),
            'contribute_as'     => $participation === 'initiative' ? $this->json($request->list('contribute_as')) : null,
            'portal_interest'   => $participation === 'initiative' ? $this->nullable($request->str('portal_interest')) : null,
            'consent_terms'     => 1,
            'consent_marketing' => $request->str('consent_marketing') === '1' ? 1 : 0,
            'ip_address'        => @inet_pton($request->ip()) ?: null,
            'user_agent'        => $this->nullable($request->userAgent()),
            'status'            => $participation === 'onsite' ? 'pending' : 'confirmed',
            'payment_status'    => $participation === 'onsite' ? 'unpaid' : 'not_required',
            'payment_amount'    => $participation === 'onsite' ? (int) config('stripe.price_pence') : null,
            'stripe_session_id' => null,
        ];

        return [[], $clean];
    }

    public function register(array $clean): array
    {
        Registration::create($clean);
        return Registration::findByReference($clean['reference']) ?? $clean;
    }

    public static function duplicateMessage(string $email): ?string
    {
        $registration = Registration::findByEmail($email);
        if ($registration === null) {
            return null;
        }
        if ($registration['status'] === 'cancelled') {
            return 'This email has a cancelled registration. Please contact the organisers before registering again.';
        }
        if ($registration['participation'] === 'onsite' && $registration['payment_status'] === 'unpaid') {
            return 'This email is already registered for onsite attendance, but payment is still outstanding. Use Complete payment with your saved reference and email; you do not need to register again.';
        }
        if ($registration['payment_status'] === 'paid') {
            return 'This email is already registered and payment is complete. Check your confirmation email for your access pass; you do not need to register or pay again.';
        }
        return 'This email is already registered. No payment is required for your registration. Check your confirmation email for your registration details.';
    }

    /** e.g. KPS26-7QK4M3 — unambiguous alphabet, no 0/O/1/I. */
    private function generateReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'KPS26-' . $code;
        } while (Registration::referenceExists($reference));

        return $reference;
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function json(array $values): ?string
    {
        return $values === [] ? null : json_encode(array_values($values), JSON_THROW_ON_ERROR);
    }
}
