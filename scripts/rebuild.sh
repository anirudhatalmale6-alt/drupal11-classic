#!/bin/sh
#
# Rebuild this site from nothing, in order.
#
# Every step is idempotent, so it is also safe to re-run against an existing
# site to re-assert the configuration.
#
# Usage:
#   sh scripts/rebuild.sh              # config + theme + sample content
#   sh scripts/rebuild.sh --no-content # config + theme only
#
set -e

DRUSH="./vendor/bin/drush"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "== Core recipes (article, page, tags, image media, search, responsive images)"
for recipe in page_content_type article_content_type article_tags image_media_type content_search standard_responsive_images; do
  # Drupal 11's Standard profile no longer creates the Article and Basic page
  # content types itself — they live in these recipes.
  $DRUSH recipe "$ROOT/web/core/recipes/$recipe" || true
done

echo "== Modules"
$DRUSH en -y \
  pathauto token honeypot metatag metatag_open_graph redirect field_group \
  content_moderation workflows views_ui media media_library responsive_image \
  menu_link_content contextual

echo "== 1. Content types and fields"
$DRUSH php:script scripts/01_content_types.php

echo "== 2. Form and view displays"
$DRUSH php:script scripts/02_displays.php

echo "== 3. Workflows, roles and permissions"
$DRUSH php:script scripts/03_workflow_roles.php

echo "== 4. Views"
$DRUSH php:script scripts/04_views.php

echo "== 5. URL aliases, spam protection, site settings"
$DRUSH php:script scripts/05_settings.php

echo "== 6. Theme and blocks"
$DRUSH theme:enable classic -y
$DRUSH config:set system.theme default classic -y
$DRUSH php:script scripts/06_theme_blocks.php

echo "== Retire the deprecated base theme and the superseded toolbar"
$DRUSH theme:uninstall stable9 -y || true
$DRUSH theme:uninstall olivero -y || true
$DRUSH pm:uninstall toolbar -y || true

if [ "$1" != "--no-content" ]; then
  echo "== 7. Sample content"
  # Images are committed under assets/placeholders; regenerate only if you want
  # a different set:  python3 scripts/make_placeholder_images.py assets/placeholders
  $DRUSH php:script scripts/07_sample_content.php

  echo "== 8. Front page"
  $DRUSH php:script scripts/08_front_page.php
fi

echo "== Rebuild caches"
$DRUSH cr

echo
echo "Done. Checks:"
$DRUSH status --field=drupal-version
$DRUSH updatedb:status
