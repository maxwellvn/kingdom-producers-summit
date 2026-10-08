# Systems design: Kingdom Producers Summit, two events

Written 1 October 2026 from an interview with the owner, with answers prefilled from the code and confirmed section by section.

## 0. How to use this brief

- Decision style: decide and list assumptions. Build end to end, test on a copy of the database, show the owner before anything is pushed.
- Work the scaling ladder top-down. Never add a rung marked "Not needed yet".
- Every piece of infrastructure added gets one line: what it fixes, what it costs, what breaks when it dies.
- Nothing in this system is cached. Keep it that way: counts, check-ins and stream state always read live.

## 1. Product

| | |
|---|---|
| Name | The Loveworld Kingdom Producers Summit |
| What it does | Registration, QR passes, check-in and a live stream for a one-day summit, now held in Manchester and in Ireland on the same day (Saturday 17 October 2026) |
| For | Loveworld members in the UK and Ireland, and the consulate team who run both events |
| Stage | Live, small |
| Scale | About 100 onsite per event, plus online viewers. The onsite figure is internal and must not appear on public pages. Exception: the unlisted, revocable /status?key= page (counts only, no personal data) |
| Traffic | Spiky: email blasts, then event day (check-in and stream at once) |
| Read/write | Read-heavy, with write bursts at registration and check-in |
| Data in 2 years | Thousands of rows, archived per edition |

## 2. Consumer side

- Web only, mobile-first. English. WCAG 2.2 AA.
- Attendees have no account. They find their pass or stream by email or KingsChat username; an HMAC-signed QR token proves the pass.
- Admins sign in with email and password (root admin from env, extra admins in `admins`), with lockout after failed attempts.
- Sessions live in MySQL (`sessions`), so the app is stateless.
- The home page is a chooser. Each event has its own site under `/manchester` and `/ireland`. Old unprefixed links redirect to Manchester.
- Journeys, each inside one event: register, confirmation by email and KingsChat with a QR pass, find my pass, watch online (one device per pass), check in at the door.
- Event isolation:
  - A pass belongs to one event. The Ireland desk refuses a Manchester pass and says which event it is for, and the other way round.
  - A pass opens only its own event's stream, chat and polls.
  - The same email may register for both events. Each registration has its own reference and QR pass.

## 3. Data and correctness

Every per-event row carries `event` (`manchester` | `ireland`), set from the URL the request came in on, never from a form field.

| Table | Per event | Notes |
|---|---|---|
| registrations | yes | unique `(event, email)` replaces unique `email` |
| attendances | via registration | check-in refused when the registration's event differs from the desk's |
| watch_passes | via registration | stream gate checks the registration's event |
| comments, prompts, prompt_answers | yes | chat and polls per event |
| announcements, announcement_deliveries | yes | audience is always within one event |
| sponsorships, commitments | yes | totals per event |
| stream_events | yes | stream log per event |
| settings | keys suffixed per event | edition, stream URL/state/title and similar; Manchester keeps the original keys |
| archives | yes | each event archived and cleared on its own |
| page_views, presence, cookie_consents, admins, login_attempts, sessions | no | site-wide |

Staleness: every kind of data above is "never stale". No cache, no replica.

Atomic and exactly-once:
- One registration per `(event, email)`: unique constraint, plus the existing duplicate check.
- One check-in per registration: transaction plus the existing guard.
- One delivery per recipient per announcement: the existing claim on `announcement_deliveries`.

Existing production data: every existing row is Manchester. Registrations since 29 September 2026 with country "Ireland" or a phone starting +353 are listed for the owner to confirm before they are moved.

Retention: archive per event at the end of an edition, as today.

## 4. Stack

PHP 8.3 with a small in-house MVC, MySQL 8.4, PDO, plain CSS and JS (GSAP for scroll motion), SQL migrations in `database/migrations` run on container start. No change.

## 5. Hosting and services

Coolify (Docker Compose) on a VPS; Hostinger SMTP; KingsChat API; Stripe; a separate HLS relay app; a standby VPS synced hourly (see `docs/standby-server.md`). No change and no new cost.

## 6. Scaling ladder

| # | Rung | State |
|---|---|---|
| 01 | Vertical scaling | Built (one VPS) |
| 02 | Horizontal + load balancer | Not needed yet |
| 03 | Stateless servers, shared sessions | Built (sessions in MySQL) |
| 04 | Connection pooling | Not needed yet |
| 05 | Index common queries | Build now: an `event` index (or composite) on every per-event table |
| 06 | Read replicas | Not needed yet |
| 07 | Cache | Not needed yet; and every table here is "never stale" |
| 08 | Queue + worker | Existing in-app paced sending; unchanged, scoped per event |
| 09 | Sharding | Not needed yet |

## 7. Background work

Announcements send in-app with claimed deliveries and resume; scheduled sends run when a page is loaded; KingsChat token refresh likewise. All of it gains an event scope. No new jobs.

## 8. Security (non-negotiable)

- CSRF on every post (unchanged).
- The event comes from the URL path segment, which the router validates against a fixed list; a posted `event` field is never trusted.
- The QR token and the watch pass resolve to a registration, and the registration's event is compared with the event of the desk or stream.
- Admin actions that change or send take the event explicitly and act only within it.
- The onsite capacity figure stays out of public views.

## 9. Operations

Coolify deploys from `main`; the migration runs on container start; health check unchanged. New test `tests/event-isolation.php`: register the same email for both events, refuse cross-event check-in and cross-event watch, and send an announcement to one event only.

| Symptom | Likely cause | Rung |
|---|---|---|
| Duplicate-email error for a person registering for the second event | old unique index still present | migration |
| Desk counts mixing | check-in not comparing events | code, not a rung |

## 10. Open questions

None blocking.

## 11. Definition of done

- Every per-event table carries `event`; existing rows are Manchester.
- Public registration, pass lookup, watch, chat, polls, sponsor and commitment pages write and read only their own event.
- Admin has a Manchester / Ireland switch that scopes every page: dashboard, registrations, initiative, issue a place, front desk, scanner, check-in search, stream, polls and chat, notifications, analytics, commitments, sponsorships, exports, edition, archive.
- Notifications and resends reach only the chosen event's people, by email and KingsChat.
- The isolation test passes, plus the existing test.
- Each event home has a sponsor band in its own theme that leads to that event's sponsor page.

## 12. Research

No new services, so no pricing research. Design references for the sponsor band come from the matched systems used for the chooser.

## 13. Diagrams

None: no new infrastructure. The topology is unchanged (users → Coolify app → MySQL, plus SMTP, KingsChat, Stripe and the HLS relay).
