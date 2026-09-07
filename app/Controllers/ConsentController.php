<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\CookieConsent;

final class ConsentController extends Controller
{
    public function store(Request $request): Response
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            return Response::json(['error' => 'Invalid JSON body.'], 400);
        }

        $action = (string) ($payload['action'] ?? '');
        if (!in_array($action, CookieConsent::ACTIONS, true)) {
            return Response::json(['error' => 'Unknown consent action.'], 422);
        }

        $categories = [];
        foreach (CookieConsent::CATEGORIES as $key) {
            $categories[$key] = filter_var($payload[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $salt = (string) config('app.key', '');
        $ipHash = $salt !== '' ? hash_hmac('sha256', $request->ip(), $salt) : null;

        CookieConsent::record(
            $action,
            $categories['preferences'],
            $categories['analytics'],
            $categories['marketing'],
            $ipHash,
            $request->userAgent(),
        );

        return Response::json(['ok' => true], 201);
    }
}
