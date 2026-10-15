# Booking review — 15 October 2026

Historical local review. The subsequent staging deployment and resolved mail
transport blocker are recorded in [the deployment report](../../staging-deployment-2026-10-15.md).

## Version and scope

Reviewed implementation: `8230e7426a7c44ae64b2d99ff777ca02098f8e27`.
While the checks were running, another local agent committed the working changes,
including the three final form fixes, and pushed them to `main`. This review branch
preserves that history and adds only verification evidence. The implementation is
already in `main`; this PR does not introduce it again.

- Required name, phone, email and hotel fields in step 2 have specific English
  messages, supplied to both CF7's browser schema and server validation.
- Invalid passenger values receive a readable 1–5 passenger / direct-contact
  message before CF7's default enum message. Existing rules remain enabled.
- The Privacy Policy link sits outside the checkbox label and has a 44 px minimum
  height in both forms.
- Shared Booking validation and the legacy admin_post handler remain in place.

No AGENTS.md was found in the project or its ancestor directories.

## Checks actually run locally

Environment: Docker WordPress, `WP_ENVIRONMENT_TYPE=local`, test mail mode enabled,
production recipient empty. The local MU plugin forces SMTP to Mailpit:1025 with
no external relay. No production settings were changed.

| Check | Result |
| --- | --- |
| PHP lint across MyTaxi Core and the child theme | All passed |
| `docker compose run --rm wpcli wp mytaxi verify` | All 66 checks passed, including shared Booking and legacy handler checks |
| Direct CF7 requests without browser validation | Empty journey, today, past date, invalid date/time, six passengers, missing return details, return before arrival, missing child age and missing hotel all rejected |
| Invalid six-passenger request | Specific English message; no “Undefined value” |
| Browser, 1440×900 and 390×844 | Specific name/phone/email/hotel/privacy messages displayed |
| Privacy link, both viewports | Measured height 44 px; outside checkbox label |
| Browser, both viewports | Return requested summary present; no horizontal overflow or JS errors |
| One synthetic valid request with return | `mail_sent`; captured in local Mailpit with synthetic Reply-To, Price on request and return details |
| Repeating that request | Aborted as duplicate; no second send |
| Git whitespace check and credential-pattern scan of implementation additions | No whitespace errors or matched private keys, GitHub/AWS tokens, credential URLs or literal passwords |

The scan is a targeted check, not a guarantee about all repository history.
The review branch contains only this report and the screenshots below: no database,
.env, SMTP credentials, request logs or temporary test scripts. Existing untracked
design sources and the installed CF7 directory are left untouched and excluded.

## Screenshots

The form fields are empty; no passenger data is shown. The desktop image includes
the service's public business phone number.

[Desktop (1440 px)](desktop.png) · [Mobile (390 px)](mobile.png)

## Demo deployment: blocked, not performed

No documented deployment procedure or confirmed deployment target/access for
`mytaxivarna.skaradzhov.com` was found in the project. More importantly, the existing
`mytaxi-local-mail.php` transport deliberately runs only in `local`, not `staging`.
Test mode selects a recipient; it does not itself intercept outgoing email.
Consequently the local Mailpit result cannot establish safe mail handling on the
demo. No deployment or test request to the demo was attempted.

Before staging deployment, provide/verify a deployment procedure and a dedicated
non-relaying test transport. Confirm `WP_ENVIRONMENT_TYPE=staging`, noindex,
`blog_public=0`, test mode and absence of real outgoing recipients. Ensure Contact
Form 7 (locally 6.2.2, installed but untracked) is available there. Keep production
configuration and mail delivery disabled.

After deployment, the staging checks still required are: empty first step, today's
date, valid transition and Edit journey, return summary, Price on request, correct
WhatsApp/phone/Viber links, mobile layout and one request captured only by the
staging test transport. None of these are claimed as verified on staging.

Client dependencies remain confirmed fares, mailbox/SMTP and SPF/DKIM/DMARC,
groups above five, same-day request policy and approval of the privacy text; see
[client questions](../../client-questions.md) and the
[deployment checklist](../../deployment-checklist.md). Real iOS/Android date/time
pickers, Safari, screen readers and real mail delivery remain unverified.
