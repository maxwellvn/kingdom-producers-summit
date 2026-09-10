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
            'phone'         => 'required|max:30|phone',
            'kingschat_username' => 'max:80',
            'country'       => 'required|min:2|max:80',
            'city'          => 'max:80',
            'age_band'      => 'required|in:' . implode(',', Registration::AGE_BANDS),
            'zone'          => 'required|max:120',
            'group_name'    => 'required|max:120',
            'church_name'   => 'required|max:160',
            'organisation'  => 'max:120',
            'role_title'    => 'max:120',
            'field'         => 'in:' . implode(',', Registration::FIELDS),
            'field_other'   => 'max:120',
            'producer_stage'=> 'in:' . implode(',', Registration::STAGES),
            'producer_stage_detail' => 'max:500',
            'interests'     => 'array|max_items:16|in:' . implode(',', array_keys(Registration::INTERESTS)),
            'interest_other'=> 'max:160',
            'hear_about'    => 'in:' . implode(',', array_keys(Registration::HEAR_ABOUT)),
            'consent_terms' => 'required|accepted',
        ];

        if ($participation === 'onsite') {
            $rules['dietary'] = 'max:160';
            $rules['accessibility'] = 'max:255';
            $rules['emergency_contact'] = 'max:160';
        }

        // ponytail: the initiative path collects personal details only
        if ($participation !== 'initiative') {
            $rules['field'] = 'required|' . $rules['field'];
            $rules['producer_stage'] = 'required|' . $rules['producer_stage'];
        }

        $labels = [
            'participation'  => 'Participation',
            'first_name'     => 'First name',
            'last_name'      => 'Surname',
            'email'          => 'Email address',
            'phone'          => 'Phone number',
            'kingschat_username' => 'KingsChat username',
            'age_band'       => 'Age group',
            'zone'           => 'Zone or BLW campus',
            'group_name'     => 'Group',
            'church_name'    => 'Church',
            'field'          => 'Field',
            'field_other'    => 'Your field',
            'producer_stage' => 'Producer stage',
            'interest_other' => 'Other area of interest',
            'consent_terms'  => 'the privacy notice',
        ];

        $data = $request->all();
        $validator = Validator::make($data, $rules, $labels);
        $errors = $validator->passes() ? [] : $validator->errors();

        $email = mb_strtolower($request->str('email'));
        // One registration per email. Changing path is an organiser action until there is an event manager.
        if (!isset($errors['email']) && $email !== '' && Registration::findByEmail($email) !== null) {
            $errors['email'] = self::duplicateMessage($email) ?? 'This email is already registered.';
        }

        // Onsite places are finite; an upgrade needs a place just as a new registration does.
        if (!isset($errors['participation']) && $participation === 'onsite' && !self::onsitePlaceAvailable()) {
            $errors['participation'] = 'Onsite places are fully booked. You can still register to participate online or join the initiative.';
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
            'kingschat_username'=> $this->nullable(ltrim($request->str('kingschat_username'), '@')),
            'country'           => $request->str('country'),
            'city'              => $this->nullable($request->str('city')),
            'age_band'          => $request->str('age_band'),
            'church_group'      => $this->nullable($request->str('church_name')),
            'zone'              => $this->nullable($request->str('zone')),
            'group_name'        => $this->nullable($request->str('group_name')),
            'church_name'       => $this->nullable($request->str('church_name')),
            'organisation'      => $this->nullable($request->str('organisation')),
            'role_title'        => $this->nullable($request->str('role_title')),
            'field'             => $this->nullable($request->str('field')),
            'field_other'       => $request->str('field') === 'Other' ? $this->nullable($request->str('field_other')) : null,
            'producer_stage'    => $this->nullable($request->str('producer_stage')),
            'producer_stage_detail' => $this->nullable($request->str('producer_stage_detail')),
            'what_to_produce'   => null,
            'interests'         => $this->json($request->list('interests')),
            'interest_other'    => $this->nullable($request->str('interest_other')),
            'hear_about'        => $this->nullable($request->str('hear_about')),
            'onsite_days'       => null,
            'dietary'           => $participation === 'onsite' ? $this->nullable($request->str('dietary')) : null,
            'accessibility'     => $participation === 'onsite' ? $this->nullable($request->str('accessibility')) : null,
            'needs_letter'      => $participation === 'onsite' && $request->str('needs_letter') === '1' ? 1 : 0,
            'emergency_contact' => $participation === 'onsite' ? $this->nullable($request->str('emergency_contact')) : null,
            'wants_updates'     => 1,
            'wants_portal'      => $participation === 'initiative' ? 1 : ($request->str('wants_portal') === '1' ? 1 : 0),
            'contribute_as'     => $participation === 'initiative' ? $this->json($request->list('contribute_as')) : null,
            'portal_interest'   => $participation === 'initiative' ? $this->nullable($request->str('portal_interest')) : null,
            'consent_terms'     => 1,
            'consent_marketing' => $request->str('consent_marketing') === '1' ? 1 : 0,
            'ip_address'        => @inet_pton($request->ip()) ?: null,
            'user_agent'        => $this->nullable($request->userAgent()),
            'status'            => $participation === 'onsite' ? 'pending' : 'confirmed',
            'payment_status'    => $participation === 'onsite' ? 'unpaid' : 'not_required',
            'payment_amount'    => $participation === 'onsite' ? (int) config('paypal.price_pence') : null,
            'payment_session_id' => null,
        ];

        return [[], $clean];
    }

    public function register(array $clean): array
    {
        Registration::create($clean);
        return Registration::findByReference($clean['reference']) ?? $clean;
    }

    /** True while onsite capacity has not been reached. */
    public static function onsitePlaceAvailable(): bool
    {
        // ponytail: a read-then-insert check, so a dead heat could seat one extra.
        // Swap for a transaction with SELECT ... FOR UPDATE if that ever matters.
        return Registration::onsiteSeatsTaken() < max(1, (int) config('app.summit.onsite_capacity'));
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
        if ($registration['participation'] === 'onsite' && $registration['payment_status'] === 'claimed') {
            return 'This email is already registered for onsite attendance and your payment is awaiting confirmation. You do not need to register or pay again.';
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
