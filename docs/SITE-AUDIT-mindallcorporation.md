# mindallcorporation.com — external audit

Everything below was measured from outside the site on **12 September 2026**,
with no credentials and no access to the server. Nothing here is a guess; where
I could not establish something without access, it is listed under
"What I still cannot see".

---

## Verified

| | |
|---|---|
| Domain | `mindallcorporation.com`, `www` resolves to the same host |
| IP | `68.66.248.48` |
| Nameservers | `ns1`–`ns4.a2hosting.com` — **A2 Hosting** |
| Web server | Apache |
| PHP | **7.3.33** (`X-Powered-By`) |
| Drupal | **8.8.4** (`?v=8.8.4` on every core asset; `X-Generator: Drupal 8`) |
| State | **Maintenance mode**, not down |
| Theme | `corporateplus` — "Corporate+", at `/themes/custom/corporateplus/` |
| Module seen | `superfish` + its JS library |
| Mail | `MX 0 mail.mindallcorporation.com` — mail is on the same host |

### The site is not broken

Every path returns `503` with Drupal's own maintenance page, titled
*"Site under maintenance | CORPORATE+"*. That is Drupal serving the 503, not
Apache failing — the application boots, connects to its database, renders a
themed page, and then deliberately refuses the request. Switching maintenance
mode off would bring it straight back.

`/user/login` returns `200` and a fully themed 92 KB page, which is the expected
behaviour: Drupal always keeps the login form reachable in maintenance mode.
Static files (`/robots.txt`, `/core/CHANGELOG.txt`, `/core/misc/drupal.js`)
serve normally because Apache hands them over without involving Drupal.

### The site still wears the theme's demo branding

The maintenance page reads *"CORPORATE+ — A DRUPAL 8 THEME FOR CORPORATE
SITES"*. That is the theme vendor's own demo text, still in the site name and
slogan settings. Consistent with the client's description: active, never
populated.

### External dependencies the current theme pulls in

- Google Fonts — Fira Sans, Libre Franklin, Source Code Pro, PT Serif
- `code.cdn.mozilla.net` — Fira
- FontAwesome kit `kit.fontawesome.com/460b00a955.js`
- Bundled jQuery plugins: countTo, Waypoints, tooltip and in-page-nav init scripts

Four separate third-party origins on every page load. Worth replacing with
self-hosted assets — faster, and no third-party requests to disclose.

---

## The two real blockers

### 1. PHP 7.3 versus Drupal 11's floor

| | Requires |
|---|---|
| Current site (Drupal 8.8) | PHP 7.0.8–7.4 |
| Drupal 11 | **PHP 8.3+** |

The ranges do not overlap. There is no PHP version on which both the current
site and Drupal 11 will run, so the PHP switch and the Drupal switch have to
happen together — flipping PHP to 8.3 under the existing Drupal 8.8 install
produces a fatal error, not a working site.

A2 Hosting normally exposes a PHP version selector in cPanel, so the change
itself should be quick. **Do not make it before the new site is ready.**

### 2. Drupal 8 has been end-of-life for nearly five years

Drupal 8 reached end of life on **2 November 2021**. 8.8.4 dates from March 2020,
so the install has missed every core security release since — including the
Drupal 9/10-era advisories that also applied to 8.x code paths.

Maintenance mode narrows the attack surface considerably, since Drupal refuses
almost every request. It does not remove it: `/user/login` is still live and
still running five-year-old code, and `/core/install.php` answers with a `200`.

This is the reason to move, rather than to leave it sitting.

---

## Module and theme audit

Only what is observable from the login page. A full audit needs the file system.

| Project | Current | Drupal 11 ready? | Action |
|---|---|---|---|
| `superfish` | 8.x-1.x | **Yes** — 8.x-1.16 declares `^9 \|\| ^10 \|\| ^11` | Update in place; no replacement needed |
| `corporateplus` (Corporate+) | Drupal 8 theme | **No** — it identifies itself as a Drupal 8 theme | Replace |

Corporate+ is a commercial theme. If the vendor now ships a Drupal 10/11 edition
and the licence is still held, porting is an option worth pricing. Otherwise it
is replaced — which the brief asks for anyway ("replace the current theme with a
fully responsive, classic-and-elegant design").

---

## Recommendation: build alongside, do not upgrade in place

The brief's own framing settles this. An in-place upgrade means four consecutive
compatibility gauntlets — 8.8 → 8.9 → 9 → 10 → 11 — and each one exists to carry
a database forward. Here:

- there is **no content** to carry forward, by the owner's own account
- the **theme is being replaced** regardless, so its D8-only code is irrelevant
- the only contributed module found is already **Drupal 11 compatible**

So the in-place route spends all its effort preserving things nobody wants
preserved.

**Proposed sequence**

1. Full backup of the existing install — database dump and files — taken off the
   server and verified by restoring it once.
2. Stand up Drupal 11 in a subdirectory or subdomain on the same A2 account,
   under its own PHP 8.3 handler if the host allows per-directory PHP versions.
   If it does not, build it elsewhere and move it at cutover.
3. Configure content types, theme, logo and pages there. The live site is never
   touched and stays in maintenance mode throughout.
4. Client reviews on a real URL.
5. Cutover: switch PHP to 8.3, swap the docroot, `drush updatedb`, `drush cr`,
   smoke-test, maintenance mode off.
6. Old install kept as a dated archive for a fortnight.

Rollback at any point before step 5 is "do nothing". Rollback after step 5 is
"put the old docroot back and set PHP to 7.3" — minutes, not hours.

`UPGRADE.md` in this repository documents the in-place route in full as well, so
that option stays open and so future major upgrades follow a written path.

---

## What I still cannot see

Needs cPanel or SSH:

- The full module list, and whether any are patched or custom
- Whether any custom code exists beyond the theme
- Actual node/user counts — everything but the login form is behind the 503
- Database engine and version. **This matters**: Drupal 11 needs MySQL 8.0.35+
  or MariaDB 10.6+, and that is the one requirement a shared host cannot always
  satisfy. Worth establishing early.
- Disk usage and whether the files directory holds anything worth keeping
- Whether the Corporate+ licence is current

Needs the client:

- The logo, at the highest resolution available
- What Mindall Corporation does, and which pages the site should have
- Brand colours, or a reference site
