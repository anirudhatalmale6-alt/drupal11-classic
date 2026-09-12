<?php

/**
 * @file
 * Step 8 — front page: hero block plus a featured catalogue strip.
 *
 * Run with: drush php:script scripts/08_front_page.php
 */

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;

// The Journal listing is the front page, with a hero above it.
\Drupal::configFactory()->getEditable('system.site')->set('page.front', '/journal')->save();

if (!BlockContentType::load('basic')) {
  BlockContentType::create([
    'id' => 'basic',
    'label' => 'Basic block',
    'revision' => FALSE,
  ])->save();
  block_content_add_body_field('basic');
  echo "+ basic block type\n";
}

/**
 * Creates a custom block by info string, or returns the existing one.
 */
function classic_block_content(string $info, string $html): BlockContent {
  $existing = \Drupal::entityTypeManager()->getStorage('block_content')
    ->loadByProperties(['info' => $info]);
  if ($existing) {
    echo "= block content: $info\n";
    return reset($existing);
  }
  $block = BlockContent::create([
    'type' => 'basic',
    'info' => $info,
    'body' => ['value' => $html, 'format' => 'full_html'],
  ]);
  $block->save();
  echo "+ block content: $info\n";
  return $block;
}

$hero = classic_block_content('Front page hero', <<<HTML
<span class="classic-eyebrow">Est. placeholder</span>
<h1 class="classic-hero__title">Considered writing, considered things.</h1>
<p class="classic-hero__lede">A journal about making, mending and living with objects — and a small catalogue of the ones we would keep.</p>
<p class="classic-hero__actions">
  <a class="button" href="/catalogue">Browse the catalogue</a>
  <a class="button button--ghost" href="/community">Read our readers</a>
</p>
HTML);

// Front page only. page.front points at /journal, so both paths are listed:
// '<front>' matches the alias the visitor typed, '/journal' the resolved route.
$visibility = [
  'request_path' => [
    'id' => 'request_path',
    'negate' => FALSE,
    'pages' => "<front>\n/journal",
  ],
];

if ($existing = Block::load('classic_hero')) {
  $existing->delete();
}
Block::create([
  'id' => 'classic_hero',
  'theme' => 'classic',
  'region' => 'hero',
  'weight' => 0,
  'plugin' => 'block_content:' . $hero->uuid(),
  'settings' => [
    'id' => 'block_content:' . $hero->uuid(),
    'label' => 'Front page hero',
    'label_display' => '0',
    'provider' => 'block_content',
    'view_mode' => 'full',
  ],
  'visibility' => $visibility,
])->save();
echo "block classic_hero -> hero\n";

// A short catalogue strip under the journal listing on the front page.
if ($existing = Block::load('classic_front_catalogue')) {
  $existing->delete();
}
Block::create([
  'id' => 'classic_front_catalogue',
  'theme' => 'classic',
  'region' => 'content_below',
  'weight' => 5,
  'plugin' => 'views_block:classic_catalogue-block_1',
  'settings' => [
    'id' => 'views_block:classic_catalogue-block_1',
    'label' => 'From the catalogue',
    'label_display' => 'visible',
    'provider' => 'views',
    'views_label' => 'From the catalogue',
    'items_per_page' => '3',
  ],
  'visibility' => $visibility,
])->save();
echo "block classic_front_catalogue -> content_below\n";

echo "Done.\n";
