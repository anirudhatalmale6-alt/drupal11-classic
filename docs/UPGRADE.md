# Drupal 8 → 11: the runbook

There is no direct 8 → 11 jump. Each major release drops APIs the previous one
deprecated, so the path is 8.9 → 9.5 → 10.3/10.4 → 11. Composer will refuse to
skip a step, and it is right to.

This document is written to be followed twice: once now, and again in two years
when 12 lands.

---

## 0. Decide which route you are taking

**Route A — upgrade in place.** Keep the existing database, walk it up through
each major version. Correct when the site has real content, real users, and
custom modules whose data lives in the database.

**Route B — rebuild on 11 and port.** Stand up a clean Drupal 11, recreate the
configuration, and bring across only what matters. Much less risk, and it leaves
you with no accumulated debris.

**For this site, Route B is the recommendation.** The brief says the Drupal 8
installation has never been populated with real content. If that is true, almost
everything Route A carefully preserves is empty. Route B skips four
compatibility gauntlets and lands on a site with no legacy modules, no orphaned
config entities, and no half-migrated fields.

Route A is written out in full below anyway, because the reasoning transfers to
every future upgrade.

---

## 1. Before touching anything

```sh
# A database dump you can actually restore.
drush sql:dump --gzip --result-file=../backup-pre-upgrade.sql.gz

# The files directory.
tar czf ../backup-files.tar.gz web/sites/default/files

# Write down exactly what you are starting from.
drush status
drush pm:list --status=enabled --format=table > ../modules-before.txt
drush config:export -y && git add -A && git commit -m "Config snapshot before upgrade"
```

Restore the dump onto a scratch database once, now, and confirm the site comes
up. A backup you have never restored is a hope, not a backup.

**Do all of this on a copy.** Never upgrade the live site in place; cut over
only once the copy passes.

## 2. Check what the host can actually run

| Drupal | PHP | MySQL | MariaDB | PostgreSQL | SQLite |
|---|---|---|---|---|---|
| 9 | 7.3+ (8.0 for 9.4+) | 5.7.8+ | 10.3.7+ | 10+ | 3.26+ |
| 10 | 8.1+ | 5.7.8+ | 10.3.7+ | 12+ | 3.26+ |
| 11 | 8.3+ | 8.0.35+ | 10.6+ | 16+ | 3.45+ |

```sh
php -v
drush sql:query "SELECT VERSION();"
```

Drupal 11's database floor is the one that bites: plenty of shared hosts still
run MySQL 5.7 or MariaDB 10.4, and no amount of Composer work gets you past it.
Establish this **first** — if the host cannot do PHP 8.3 and MySQL 8.0.35, the
upgrade is a hosting conversation before it is a code one.

## 3. Get onto Composer, if you are not already

A Drupal 8 site installed from a tarball has no `composer.json` worth the name.
Everything below assumes Composer manages core and contrib.

```sh
composer create-project drupal/legacy-project:^8.9 upgrade-tmp --no-install
```

Then move `web/sites`, `web/modules/custom`, `web/themes/custom` and
`web/libraries` across, and re-require each contributed module by name. Tedious,
once.

## 4. 8.x → 8.9

8.9 is the last 8.x release and the only version that can hand over to 9.

```sh
composer require drupal/core-recommended:^8.9 drupal/core-composer-scaffold:^8.9 --update-with-all-dependencies
drush updatedb -y
drush cache:rebuild
```

## 5. Find out what is actually blocking 9

Install Upgrade Status. It is the single most useful hour in this whole process.

```sh
composer require --dev drupal/upgrade_status
drush en upgrade_status -y
```

Then visit **Reports → Upgrade status**. It reports three things:

1. Contributed modules with no Drupal 9 release. Each is a decision: find a
   replacement, patch it, or drop the feature.
2. Deprecated API calls in your custom code, file and line.
3. Environment problems — PHP version, database version, `drupal/core` version
   constraints in `composer.json`.

For custom code, `drupal-check` gives the same answers on the command line:

```sh
composer require --dev mglaman/drupal-check
./vendor/bin/drupal-check -ad web/modules/custom web/themes/custom
```

Fix every custom-code finding **before** bumping core. Deprecated calls still
work on 8.9; on 9 they fatal.

Common Drupal 8 → 9 fixes:

- `drupal_set_message()` → `\Drupal::messenger()->addStatus()`
- `entity_load()` / `entity_create()` → `\Drupal::entityTypeManager()->getStorage(...)`
- `file_prepare_directory()` → `\Drupal::service('file_system')->prepareDirectory()`
- `db_query()` → `\Drupal::database()->query()`
- `format_date()` → `\Drupal::service('date.formatter')->format()`
- `core_version_requirement: ^8 || ^9` in every custom `.info.yml`
- Themes: `base theme: classy` → copy Classy's templates in, or drop the base

## 6. 8.9 → 9

```sh
# Contrib first — a module pinned to ^8 blocks the core bump.
composer require 'drupal/pathauto:^1.8' 'drupal/token:^1.9' --update-with-dependencies
# ...one line per contributed module, at a version with D9 support.

composer require drupal/core-recommended:^9 drupal/core-composer-scaffold:^9 drupal/core-project-message:^9 --update-with-all-dependencies

drush updatedb -y
drush cache:rebuild
drush config:export -y      # inspect the diff: core rewrites config on major upgrades
```

If Composer produces a wall of conflicts, resolve them one package at a time
(`composer why-not drupal/core-recommended 9`). Do not reach for
`--ignore-platform-reqs`; it only moves the failure to runtime.

## 7. 9 → 10

Bring 9 fully up to 9.5 first, then:

```sh
drush en upgrade_status -y   # run it again — the deprecation set is different
```

The 10 jump has three specific traps:

- **CKEditor 4 is gone.** Run the *CKEditor 5 upgrade* at
  `/admin/config/content/formats` for each text format before upgrading, and
  check every format afterwards — buttons silently disappear if a plugin has no
  CKEditor 5 equivalent.
- **Classy and Stable are gone.** A theme with `base theme: classy` will not
  boot on 10. Either use `stable9`/`classy` as contributed projects, or copy the
  templates you actually use into your own theme.
- **jQuery UI is almost entirely gone.** Custom JS leaning on
  `core/jquery.ui.*` needs rewriting.

```sh
composer require drupal/core-recommended:^10 drupal/core-composer-scaffold:^10 drupal/core-project-message:^10 --update-with-all-dependencies
drush updatedb -y
drush cache:rebuild
```

## 8. 10 → 11

The gentlest of the three, provided PHP and the database are already at 11's
floor.

```sh
drush en upgrade_status -y   # once more
composer require drupal/core-recommended:^11 drupal/core-composer-scaffold:^11 drupal/core-project-message:^11 --update-with-all-dependencies
drush updatedb -y
drush cache:rebuild
```

What changed that is easy to miss:

- **The Standard profile no longer creates Article and Basic page.** Those
  content types moved into recipes (`core/recipes/article_content_type`,
  `core/recipes/page_content_type`). A fresh 11 install has neither until you
  apply them. This bit this build; see `scripts/rebuild.sh`.
- **stable9 is deprecated** and goes in Drupal 12. If your theme inherits it,
  either set `base theme: false` (this build's choice) or generate a standalone
  theme with `php core/scripts/dr generate-theme`.
- **Toolbar is superseded by Navigation.** Both installed together is a warning
  in the status report; uninstall Toolbar.
- **`node_add_body_field()`** is deprecated in 11.3 and removed in 12. Create
  the body field instance directly — see `classic_body_field()` in
  `scripts/01_content_types.php`.
- **Several contrib modules moved into core** or were replaced. Check each one
  before re-requiring it.

## 9. After each step, not just at the end

```sh
drush status                          # version, database, bootstrap
drush updatedb:status                 # must say "No database updates required"
drush core:requirements --severity=2  # must be empty
drush watchdog:show --count=50        # look for new errors
```

Then click through, logged out and logged in: front page, each listing, one node
of each type, one form submission, the login form, and search. Automate what you
can — `web/tests` or a short Playwright script beats memory.

## 10. Cutting over

1. Put the live site into maintenance mode.
2. Take a final dump.
3. Restore it onto the upgraded codebase and run `drush updatedb -y` once more —
   content will have changed since your working copy was taken.
4. `drush cr`, then smoke-test on the real domain.
5. Maintenance mode off.
6. Keep the pre-upgrade dump for a fortnight.

---

## Keeping it painless next time

- **Config in the repository.** `drush config:export` after every change,
  commit it. `config/sync` is the site's build definition; the database is just
  its content.
- **Never edit core or contrib in place.** Patches go through
  `cweagans/composer-patches` so they survive the next update.
- **Minor updates monthly.** `composer update drupal/core-* --with-dependencies`
  then `drush updatedb`. A site that is current on 11.4 is a morning's work to
  get to 12; a site still on 11.0 is a fortnight.
- **Run Upgrade Status once a quarter**, not once a major version. It reports
  deprecations while they are still just deprecations.
- **Subscribe to security advisories** for every contributed module you use.
- **Keep custom code small.** Every custom module is a thing that has to be
  re-checked at each major version. The three content types, four Views and one
  theme here deliberately use no custom module at all.
