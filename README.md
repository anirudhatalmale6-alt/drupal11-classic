# Classic — Drupal 11 build

A Drupal 11 site with a custom classic/elegant theme, three content types with
moderation, sample content, and roles that let visitors submit content without
opening the editorial back end.

Current state: **Drupal 11.4.6**, no outstanding database updates, no errors in
the status report.

---

## What is in here

```
config/sync/                 exported site configuration (291 files)
scripts/                     idempotent build scripts — see "Rebuilding" below
web/themes/custom/classic/   the theme
docs/UPGRADE.md              the Drupal 8 → 11 runbook
docs/EDITOR-GUIDE.md         day-to-day guide for whoever runs the site
docs/SITE-AUDIT-…            what the existing Drupal 8 site is running
```

## Content types

| Machine name | Label | Fields beyond title/body | Workflow |
|---|---|---|---|
| `article` | Article | Standfirst, Category, Tags, Image, Reading time | Editorial |
| `product` | Product Listing | Images (×3), SKU, Price, Availability, Category, Materials (×5), Dimensions | Editorial |
| `submission` | User-Generated Entry | Image, Category, Contributor name, Location | Community submission |
| `page` | Basic page | — | Editorial |

## Views

| View | Path | Style |
|---|---|---|
| Journal | `/journal` (also the front page) | Teaser list, 10 per page, plus a footer block |
| Catalogue | `/catalogue` | 3-up card grid, exposed category filter, plus a 3-item block |
| Community | `/community` | Teaser list, published entries only |
| Moderation queue | `/admin/content/queue` | Table of everything unpublished |

## Roles

| Role | Can | Cannot |
|---|---|---|
| Anonymous | Read published content, search | Submit anything (see below) |
| Contributor | Create and edit their own entries, submit for review | Publish anything, reach `/admin` |
| Content editor | Everything editorial: create/edit/delete all types, approve or reject submissions, manage taxonomy and media | Change site configuration, manage modules or users |
| Administrator | Everything | — |

**Anonymous submission is off by default.** New accounts are set to
*Visitors, but administrator approval is required*, so a contributor account is
a deliberate act. To let anonymous visitors post instead, grant the anonymous
role `create submission content` and
`use community_submission transition submit_for_review` — honeypot already
protects the form, and nothing reaches the site without an editor approving it.

## The Community submission workflow

```
        submit_for_review              approve
Draft ─────────────────────> Needs review ────────> Published
  ^                               │                     │
  │          send_back            │      reject         │
  └───────────────────────────────┴─────────────────────┘
                                  ↓
                              Rejected ──submit_for_review──> Needs review
```

`Rejected` is the default revision, so moving a published entry to it also takes
it off the site — that is the unpublish path. Workflows refuses two transitions
over the same published → rejected pair, which is why there is no separate
"unpublish".

It is a separate workflow from Editorial on purpose. A contributor only holds
`create_new_draft` and `submit_for_review`; there is no checkbox that would give
them a publishing transition by accident.

## Demo accounts

| Login | Password | Role |
|---|---|---|
| `admin` | `Admin12345!` | Administrator |
| `Margaret Ashcombe` | `Demo12345!` | Content editor |
| `Tomas Iversen` | `Demo12345!` | Contributor |

These are for the demo build only. Change or delete them before launch.

## Running it locally

Requires PHP 8.3+, Composer 2, and either SQLite 3.45+ or MySQL 8.0.35+ /
MariaDB 10.6+.

```sh
composer install
php -S 127.0.0.1:8717 -t web web/.ht.router.php
```

The bundled database is SQLite at `web/sites/default/files/.ht.sqlite` and is
not committed. To build a site from scratch:

```sh
./vendor/bin/drush site:install standard \
  --db-url=sqlite://sites/default/files/.ht.sqlite \
  --account-name=admin --account-pass=Admin12345! -y
sh scripts/rebuild.sh
```

Or, if you have the exported config and an empty database of the same kind:

```sh
./vendor/bin/drush site:install --existing-config -y
```

## Rebuilding

`scripts/rebuild.sh` runs every build step in order. Each script is idempotent —
existing entities are left alone or re-asserted, never duplicated — so it is
also the way to re-apply configuration to a site that has drifted.

| Script | Does |
|---|---|
| `01_content_types.php` | Vocabularies, content types, fields |
| `02_displays.php` | Form displays, the `card` view mode, view displays |
| `03_workflow_roles.php` | Both workflows, roles, permissions |
| `04_views.php` | The four Views |
| `05_settings.php` | Pathauto patterns, honeypot, site and user settings |
| `06_theme_blocks.php` | Block placement for the theme |
| `07_sample_content.php` | Demo users, taxonomy terms, sample content |
| `08_front_page.php` | Front-page hero and catalogue strip |
| `99_purge_sample_content.php` | Removes everything `07` created |

`make_placeholder_images.py` generates the abstract placeholder imagery. Those
images are deliberately not photographs — replace them with real ones before
launch.

## Before launch

1. **Trusted host patterns.** `web/sites/default/settings.php` currently only
   trusts `localhost` and `127.0.0.1`. Add the real domain(s) or Drupal reports
   an error in the status report.
2. **Replace the placeholder content.** Everything in `/journal`,
   `/catalogue`, `/community`, `/about` and `/send-us-entry` is written to
   exercise the layouts, not to be read. `scripts/99_purge_sample_content.php`
   clears it in one go.
3. **Currency.** Prices show `£`, set on the `field_price` instance
   (Structure → Content types → Product Listing → Manage fields → Price →
   Prefix). One field, one change.
4. **Change the demo passwords**, or delete the demo accounts.
5. **Cron.** Automated cron is on; on a live host prefer a real system cron
   hitting `/cron/<key>` and set `automated_cron.interval` to 0.
6. **CSS/JS aggregation** is on. Turn it off while working on the theme
   (`drush config:set system.performance css.preprocess 0`), and remember that
   Drupal caches the aggregate — `drush cr` after every stylesheet edit.

## Notes on the theme

`web/themes/custom/classic` has **no base theme**. stable9 is deprecated in
Drupal 11 and removed in Drupal 12, so inheriting it would have put a removal
date on the theme. Without a base theme the templates come from the core modules
that define them, and core's improvements arrive with each update.

Two core templates are overridden because core's own markup carries no classes
at all: `field.html.twig` (adds `field--name-*`, `field--label-*`,
`field__label`, `field__item`) and `status-messages.html.twig` (adds
`messages--<type>`). The stylesheet depends on both.

Typefaces are EB Garamond and Inter, self-hosted as variable woff2 in
`fonts/` — no CDN request, nothing to consent to, and the site keeps its
typography if the CDN is unreachable.
