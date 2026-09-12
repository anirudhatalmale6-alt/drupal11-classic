<?php

/**
 * @file
 * Step 2 — form displays, view modes and view displays.
 *
 * Run with: drush php:script scripts/02_displays.php
 */

use Drupal\Core\Entity\Entity\EntityViewMode;

$repo = \Drupal::service('entity_display.repository');

/**
 * Creates a node view mode if missing.
 */
function classic_view_mode(string $id, string $label): void {
  if (EntityViewMode::load("node.$id")) {
    echo "  = view mode node.$id\n";
    return;
  }
  EntityViewMode::create([
    'id' => "node.$id",
    'targetEntityType' => 'node',
    'label' => $label,
  ])->save();
  echo "  + view mode node.$id\n";
}

echo "View modes\n";
classic_view_mode('card', 'Card');

echo "Form displays\n";

// --- Article ------------------------------------------------------------
$form = $repo->getFormDisplay('node', 'article');
$weight = 0;
foreach ([
  'title' => ['type' => 'string_textfield'],
  'field_subtitle' => ['type' => 'string_textfield'],
  'field_article_category' => ['type' => 'options_select'],
  'field_tags' => ['type' => 'entity_reference_autocomplete_tags'],
  'field_image' => ['type' => 'image_image'],
  'body' => ['type' => 'text_textarea_with_summary'],
  'field_reading_time' => ['type' => 'number'],
] as $field => $component) {
  $form->setComponent($field, $component + ['weight' => $weight++]);
}
$form->save();
echo "  article\n";

// --- Product ------------------------------------------------------------
$form = $repo->getFormDisplay('node', 'product');
$weight = 0;
foreach ([
  'title' => ['type' => 'string_textfield'],
  'field_sku' => ['type' => 'string_textfield'],
  'field_product_category' => ['type' => 'options_select'],
  'field_price' => ['type' => 'number'],
  'field_availability' => ['type' => 'options_select'],
  'field_product_image' => ['type' => 'image_image'],
  'body' => ['type' => 'text_textarea_with_summary'],
  'field_materials' => ['type' => 'string_textfield'],
  'field_dimensions' => ['type' => 'string_textfield'],
] as $field => $component) {
  $form->setComponent($field, $component + ['weight' => $weight++]);
}
$form->save();
echo "  product\n";

// --- Submission ---------------------------------------------------------
// Deliberately spare: this form is shown to the public, so it asks for as
// little as possible and exposes no taxonomy free-tagging.
$form = $repo->getFormDisplay('node', 'submission');
$weight = 0;
foreach ([
  'title' => ['type' => 'string_textfield'],
  'field_submission_category' => ['type' => 'options_select'],
  'body' => ['type' => 'text_textarea_with_summary'],
  'field_submission_image' => ['type' => 'image_image'],
  'field_contributor_name' => ['type' => 'string_textfield'],
  'field_contributor_location' => ['type' => 'string_textfield'],
] as $field => $component) {
  $form->setComponent($field, $component + ['weight' => $weight++]);
}
foreach (['promote', 'sticky', 'created', 'uid', 'path', 'url_redirects', 'meta'] as $hide) {
  $form->removeComponent($hide);
}
$form->save();
echo "  submission\n";

echo "View displays\n";

/**
 * Applies a set of components to a view display, hiding everything else listed.
 */
function classic_view_display($repo, string $bundle, string $mode, array $components, array $hide = []): void {
  $display = $repo->getViewDisplay('node', $bundle, $mode);
  if ($mode !== 'default' && $display->isNew()) {
    // A non-default display starts out inheriting; make it its own.
    $display->set('status', TRUE);
  }
  $weight = 0;
  foreach ($components as $field => $component) {
    $display->setComponent($field, $component + ['weight' => $weight++]);
  }
  foreach ($hide as $field) {
    $display->removeComponent($field);
  }
  $display->save();
  echo "  $bundle.$mode\n";
}

// Article — full.
classic_view_display($repo, 'article', 'default', [
  'field_subtitle' => ['type' => 'string', 'label' => 'hidden'],
  'field_image' => ['type' => 'responsive_image', 'label' => 'hidden', 'settings' => ['responsive_image_style' => 'wide', 'image_link' => '']],
  'body' => ['type' => 'text_default', 'label' => 'hidden'],
  'field_article_category' => ['type' => 'entity_reference_label', 'label' => 'inline', 'settings' => ['link' => TRUE]],
  'field_tags' => ['type' => 'entity_reference_label', 'label' => 'inline', 'settings' => ['link' => TRUE]],
], ['field_reading_time']);

// Article — teaser.
classic_view_display($repo, 'article', 'teaser', [
  'field_image' => ['type' => 'responsive_image', 'label' => 'hidden', 'settings' => ['responsive_image_style' => 'narrow', 'image_link' => 'content']],
  'field_subtitle' => ['type' => 'string', 'label' => 'hidden'],
  // Category stays in the teaser: the template renders it as the eyebrow above
  // the headline.
  'field_article_category' => ['type' => 'entity_reference_label', 'label' => 'hidden', 'settings' => ['link' => FALSE]],
  'body' => ['type' => 'text_summary_or_trimmed', 'label' => 'hidden', 'settings' => ['trim_length' => 220]],
  'links' => [],
], ['field_tags', 'field_reading_time']);

// Product — full.
classic_view_display($repo, 'product', 'default', [
  'field_product_image' => ['type' => 'responsive_image', 'label' => 'hidden', 'settings' => ['responsive_image_style' => 'wide', 'image_link' => '']],
  // Price and availability get their own treatment in
  // node--product--full.html.twig, so their labels stay hidden here.
  'field_price' => ['type' => 'number_decimal', 'label' => 'hidden', 'settings' => ['prefix_suffix' => TRUE, 'scale' => 2]],
  'field_availability' => ['type' => 'list_default', 'label' => 'hidden'],
  'field_sku' => ['type' => 'string', 'label' => 'inline'],
  'body' => ['type' => 'text_default', 'label' => 'hidden'],
  'field_materials' => ['type' => 'string', 'label' => 'inline'],
  'field_dimensions' => ['type' => 'string', 'label' => 'inline'],
  'field_product_category' => ['type' => 'entity_reference_label', 'label' => 'inline', 'settings' => ['link' => TRUE]],
]);

// Product — card, used by the catalogue View.
classic_view_display($repo, 'product', 'card', [
  'field_product_image' => ['type' => 'responsive_image', 'label' => 'hidden', 'settings' => ['responsive_image_style' => 'narrow', 'image_link' => 'content']],
  'field_price' => ['type' => 'number_decimal', 'label' => 'hidden', 'settings' => ['prefix_suffix' => TRUE, 'scale' => 2]],
  'field_availability' => ['type' => 'list_default', 'label' => 'hidden'],
], ['body', 'field_sku', 'field_materials', 'field_dimensions', 'field_product_category', 'links']);

// Submission — full.
classic_view_display($repo, 'submission', 'default', [
  'field_submission_image' => ['type' => 'responsive_image', 'label' => 'hidden', 'settings' => ['responsive_image_style' => 'wide', 'image_link' => '']],
  'body' => ['type' => 'text_default', 'label' => 'hidden'],
  'field_contributor_name' => ['type' => 'string', 'label' => 'inline'],
  'field_contributor_location' => ['type' => 'string', 'label' => 'inline'],
  'field_submission_category' => ['type' => 'entity_reference_label', 'label' => 'inline', 'settings' => ['link' => TRUE]],
]);

// Submission — teaser.
classic_view_display($repo, 'submission', 'teaser', [
  'field_submission_image' => ['type' => 'responsive_image', 'label' => 'hidden', 'settings' => ['responsive_image_style' => 'narrow', 'image_link' => 'content']],
  'body' => ['type' => 'text_summary_or_trimmed', 'label' => 'hidden', 'settings' => ['trim_length' => 200]],
  'field_contributor_name' => ['type' => 'string', 'label' => 'hidden'],
  'links' => [],
], ['field_submission_category', 'field_contributor_location']);

echo "Done.\n";
