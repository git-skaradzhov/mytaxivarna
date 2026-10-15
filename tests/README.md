# Route / price binding regression

Run in the local Docker project with CF7 active and the published Golden Sands
route present. Run cases sequentially, each in a fresh process because CF7 keeps a
submission singleton:

```sh
for scenario in destination origin valid service; do
  docker compose run --rm -v "$PWD/tests:/tests:ro" wpcli wp eval-file /tests/route-price-binding.php "$scenario" || break
done
docker compose run --rm wpcli wp mytaxi verify
```

The CF7 tests temporarily set a confirmed €45 Golden Sands fare and a synthetic
recipient. They restore the original price metadata and mail options in `finally`.
Every email is intercepted by `pre_wp_mail`; neither SMTP nor Mailpit is used.
They reject non-local environments.

- `destination`: changing Golden Sands to Albena fails validation before mail,
  despite the confirmed fare.
- `origin`: changing Varna Airport to Sofia Airport likewise fails.
- `valid`: the captured mail contains Varna Airport → Golden Sands and €45.
- `service`: a service-page enquiry can specify Albena and does not inherit €45.
- `wp mytaxi verify`: also covers the legacy handler's changed-destination refusal.

Verified locally on 15 October 2026: all four CF7 cases and all 67 built-in checks
passed. PHP lint passed on the five changed PHP implementation files. Chrome at
1440×900 and 390×844 passed readonly endpoints, canonical summary, Edit journey,
client-side tampering refusal, ignoring old draft endpoints, navigation through
Choose another route → routes → Albena, and no horizontal overflow or JS errors.
The browser check used the normal Price on request data; the confirmed-fare cases
were exercised by the CF7 tests above. No deployment or real email was performed.
