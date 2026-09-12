<?php

/**
 * @file
 * Step 6 — enable the Classic theme and place its blocks.
 *
 * Run with: drush php:script scripts/06_theme_blocks.php
 */

use Drupal\block\Entity\Block;

/**
 * Places a block, replacing any earlier placement with the same id.
 */
function classic_block(string $id, string $plugin, string $region, int $weight, array $settings = [], array $visibility = []): void {
  if ($existing = Block::load($id)) {
    $existing->delete();
  }
  Block::create([
    'id' => $id,
    'theme' => 'classic',
    'region' => $region,
    'weight' => $weight,
    'plugin' => $plugin,
    'settings' => $settings + [
      'id' => $plugin,
      'label' => '',
      'label_display' => '0',
      'provider' => explode(':', $plugin)[0],
    ],
    'visibility' => $visibility,
  ])->save();
  echo "  block $id -> $region\n";
}

// Installing a theme makes Drupal copy the previous theme's block layout into
// it, region name for region name. That leaves a duplicate main menu and a
// branding block this theme's page template already draws itself, so clear the
// theme's blocks and place only the intended set.
$purged = 0;
foreach (Block::loadMultiple() as $block) {
  if ($block->getTheme() === 'classic') {
    $block->delete();
    $purged++;
  }
}
echo "Cleared $purged inherited block placements\n";

echo "Blocks\n";

classic_block('classic_primary_menu', 'system_menu_block:main', 'primary_menu', 0, [
  'label' => 'Main navigation',
  'provider' => 'system',
  'level' => 1,
  'depth' => 2,
  'expand_all_items' => FALSE,
]);

classic_block('classic_breadcrumbs', 'system_breadcrumb_block', 'breadcrumb', 0, [
  'label' => 'Breadcrumbs',
  'provider' => 'system',
]);

classic_block('classic_messages', 'system_messages_block', 'highlighted', 0, [
  'label' => 'Status messages',
  'provider' => 'system',
]);

classic_block('classic_page_title', 'page_title_block', 'content_above', -10, [
  'label' => 'Page title',
  'provider' => 'core',
]);

classic_block('classic_local_tasks', 'local_tasks_block', 'content_above', -5, [
  'label' => 'Tabs',
  'provider' => 'core',
  'primary' => TRUE,
  'secondary' => TRUE,
]);

classic_block('classic_local_actions', 'local_actions_block', 'content_above', -4, [
  'label' => 'Primary admin actions',
  'provider' => 'core',
]);

classic_block('classic_help', 'help_block', 'content_above', -3, [
  'label' => 'Help',
  'provider' => 'help',
]);

classic_block('classic_content', 'system_main_block', 'content', 0, [
  'label' => 'Main page content',
  'provider' => 'system',
]);

// Latest articles, from the Journal view — shown on the front page only, so it
// does not repeat underneath the Journal listing itself.
classic_block('classic_latest_articles', 'views_block:classic_journal-block_1', 'footer_first', 0, [
  'label' => 'From the journal',
  'label_display' => 'visible',
  'provider' => 'views',
  'views_label' => '',
  'items_per_page' => 'none',
]);

classic_block('classic_footer_menu', 'system_menu_block:footer', 'footer_second', 0, [
  'label' => 'More',
  'label_display' => 'visible',
  'provider' => 'system',
  'level' => 1,
  'depth' => 1,
]);

classic_block('classic_account_menu', 'system_menu_block:account', 'footer_third', 0, [
  'label' => 'Your account',
  'label_display' => 'visible',
  'provider' => 'system',
  'level' => 1,
  'depth' => 1,
]);

echo "Done.\n";
