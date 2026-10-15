# Staging deployment — 15 October 2026

Target: https://mytaxivarna.skaradzhov.com/ only. Production was not touched.

The user explicitly requested deployment after supplying the server procedure.
The route binding branch was fast-forwarded into `main`, pushed to GitHub and
pulled on staging with `git pull --ff-only origin main`.
Implementation version: `ce3d3eb` (includes `c6bbdef`, `eb972de` and the staging
mail capture). A subsequent documentation-only commit records this report.
Previous deployed version: `2f5c803`.

Before updating, a database backup was saved outside the web root with mode 0600.
During the update the test recipient was temporarily emptied to disable requests.
Staging now has environment `staging`, `blog_public=0`, `noindex, nofollow`,
mail mode `test`, empty production recipient and synthetic `staging@example.test`.

## Mail

`mytaxi-staging-mail.php` intercepts `wp_mail` before any transport. Captures are
stored outside the web root in a private system-temp directory, mode 0700;
individual capture files are mode 0600. It fails closed on capture failure.
This applies only to staging; local Mailpit and production behavior are unchanged.
No real email was sent. Staging captures are inspected via SSH, not local Mailpit.

Exactly one synthetic browser request was submitted. The private capture was
verified to contain Golden Sands, the correct route endpoints and €90 for both
journeys, addressed only to the synthetic recipient. One matching capture exists.

## Checks performed on staging

- Chrome at 1440×900 and 390×844: empty first step gives field errors; today's
  date requires direct contact; valid transition, Edit journey and preserved time.
- Return summary: €45 outbound + €45 return = €90; readonly route endpoints.
- WhatsApp, Viber and telephone point to Varna Airport Transfers (+359888850884).
- No horizontal overflow or JavaScript errors in the checked route flow.
- Homepage, routes, airport transfers, Taxi Golden Sands and Sofia route load
  correctly with noindex; checked at mobile width in the same browser session.
- Price on request: temporarily enabled the sample flag on Balchik, fetched the
  public page and verified Price on request with no numeric price data attribute.
  Restored the €58 demonstration fare immediately afterward.
- Five demonstration fares are enabled only in staging data: €45, €52, €40,
  €58 and €35. They are demonstration amounts, not client-confirmed tariffs.
- CF7 6.2.2 is active. The existing untracked server CF7 installation was preserved.
- Tracked WebP assets were deployed through Git. No photo-import sources changed;
  no import rsync or photo/setup command was required.
- Cross-domain migration redirects were not enabled.

The initial HTTP check exposed 403 because the backup's restrictive umask had
also affected files created by git pull. Public tracked WordPress files and
directories were restored to 0644/0755, the backup stayed 0600, and the HTTP and
browser checks above passed afterward. The deployment checklist now scopes the
restrictive umask to backup creation.

The existing local-taxi success wording and yesterday-date validation issues in
[the customer review](customer-testing-guide.md) were not changed by deployment.
Real mobile devices, Safari, screen readers and real mail delivery remain untested.

## Последващ requirements release — 15 октомври 2026

По изрично потвърждение целта отново е демото, не реалният mytaxivarna.com.
Branch `feat/requirements-completion` е включен чрез fast-forward в main и
implementation commit `7a297ff` е качен на staging. Преди обновяването е направен
нов частен DB backup извън web root, с права 0600. Git pull е с umask 022.

След code deployment е изпълнен `~/bin/wp mytaxi content` и cache flush.
Проверено: content revision 1, индивидуален Sofia текст и 15 route FAQ,
запазени staging/noindex/test mode и празен production получател.

Chrome 1440/390: празна стъпка, днес, валидно преминаване, Edit journey,
връщане и €45 (demo) + €45 (demo) = €90 с изрична demo бележка. Контактите
са за Varna Airport Transfers; няма overflow или JS грешки. Проверени са
индивидуалното съдържание/FAQ на Sofia, липсата на demo Offer schema,
местният date minimum днес и Privacy grid/44 px. Непопълнените GBP връзки
не се представят с измислени профили. Местното CF7 съобщение вече не споменава полет.

Една нова синтетична заявка е прихваната преди транспорта в частен capture,
с правилния маршрут, €90 и demo бележка. Не е изпращана реална поща.
Реалният mytaxivarna.com, старите сайтове и production redirects са непроменени.

Пълната историческа SEO миграция, потвърдените GBP URL адреси и останалите
външни зависимости в requirements плана остават незавършени.
