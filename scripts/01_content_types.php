<?php

/**
 * @file
 * Step 1 — vocabularies, content types and fields.
 *
 * Run with: drush php:script scripts/01_content_types.php
 * Idempotent: existing entities are left alone.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

$display_repo = \Drupal::service('entity_display.repository');

/**
 * Creates a vocabulary if it does not exist yet.
 */
function classic_vocabulary(string $id, string $name, string $description): void {
  if (Vocabulary::load($id)) {
    echo "  = vocabulary $id\n";
    return;
  }
  Vocabulary::create([
    'vid' => $id,
    'name' => $name,
    'description' => $description,
  ])->save();
  echo "  + vocabulary $id\n";
}

/**
 * Creates a node type if it does not exist yet.
 */
function classic_node_type(string $id, string $name, string $description): void {
  if (NodeType::load($id)) {
    echo "  = content type $id\n";
    return;
  }
  NodeType::create([
    'type' => $id,
    'name' => $name,
    'description' => $description,
    'new_revision' => TRUE,
    'preview_mode' => DRUPAL_OPTIONAL,
    'display_submitted' => TRUE,
  ])->save();
  classic_body_field($id, 'Description');
  echo "  + content type $id\n";
}

/**
 * Attaches the shared node body field to a bundle.
 *
 * node_add_body_field() is deprecated in 11.3 and gone in 12, so the instance is
 * created here directly. The storage itself ships with the article recipe.
 */
function classic_body_field(string $bundle, string $label): void {
  $storage = FieldStorageConfig::loadByName('node', 'body');
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
      'cardinality' => 1,
    ]);
    $storage->save();
  }
  if (FieldConfig::loadByName('node', $bundle, 'body')) {
    return;
  }
  FieldConfig::create([
    'field_storage' => $storage,
    'bundle' => $bundle,
    'label' => $label,
    'settings' => ['display_summary' => TRUE, 'allowed_formats' => []],
  ])->save();
}

/**
 * Creates a field storage + instance if they do not exist yet.
 */
function classic_field(string $bundle, string $name, string $type, string $label, array $storage_settings = [], array $settings = [], int $cardinality = 1, bool $required = FALSE): void {
  if (!FieldStorageConfig::loadByName('node', $name)) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $storage_settings,
    ])->save();
  }
  if (FieldConfig::loadByName('node', $bundle, $name)) {
    echo "  = field $bundle.$name\n";
    return;
  }
  FieldConfig::create([
    'field_name' => $name,
    'entity_type' => 'node',
    'bundle' => $bundle,
    'label' => $label,
    'required' => $required,
    'settings' => $settings,
  ])->save();
  echo "  + field $bundle.$name\n";
}

/**
 * Settings for an entity reference field limited to given vocabularies.
 */
function classic_term_ref(array $vids): array {
  return [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => array_combine($vids, $vids),
      'auto_create' => FALSE,
      'sort' => ['field' => 'name', 'direction' => 'asc'],
    ],
  ];
}

echo "Vocabularies\n";
classic_vocabulary('article_category', 'Article categories', 'Sections used to group articles and blog posts.');
classic_vocabulary('product_category', 'Product categories', 'Catalogue sections used to group product listings.');
classic_vocabulary('submission_category', 'Submission categories', 'Sections a visitor can file a community entry under.');

echo "Content types\n";
classic_node_type('product', 'Product Listing', 'An item in the catalogue, with pricing, availability and imagery.');
classic_node_type('submission', 'User-Generated Entry', 'An entry submitted by a visitor. Always moderated before it appears publicly.');
// Re-assert the body instances: a bundle created by an earlier, failed run of
// this script would otherwise keep a type with no body field.
classic_body_field('product', 'Description');
classic_body_field('submission', 'Your entry');

echo "Article fields\n";
classic_field('article', 'field_subtitle', 'string', 'Standfirst', ['max_length' => 255], [], 1, FALSE);
classic_field('article', 'field_article_category', 'entity_reference', 'Category', ['target_type' => 'taxonomy_term'], classic_term_ref(['article_category']), 1, FALSE);
classic_field('article', 'field_reading_time', 'integer', 'Reading time (minutes)', [], ['min' => 1, 'max' => 240], 1, FALSE);

echo "Product fields\n";
classic_field('product', 'field_product_image', 'image', 'Images', [], ['file_directory' => 'products/[date:custom:Y]-[date:custom:m]', 'alt_field_required' => TRUE], 3, FALSE);
classic_field('product', 'field_sku', 'string', 'SKU', ['max_length' => 64], [], 1, TRUE);
classic_field('product', 'field_price', 'decimal', 'Price', ['precision' => 10, 'scale' => 2], ['min' => 0], 1, TRUE);
// Note: the API takes the simple key => label form. ListItemBase::simplifyAllowedValues()
// converts it to the structured value/label list that lands in config.
classic_field('product', 'field_availability', 'list_string', 'Availability', [
  'allowed_values' => [
    'in_stock' => 'In stock',
    'made_to_order' => 'Made to order',
    'out_of_stock' => 'Out of stock',
    'discontinued' => 'Discontinued',
  ],
], [], 1, TRUE);
classic_field('product', 'field_product_category', 'entity_reference', 'Category', ['target_type' => 'taxonomy_term'], classic_term_ref(['product_category']), 1, FALSE);
classic_field('product', 'field_materials', 'string', 'Materials', ['max_length' => 255], [], 5, FALSE);
classic_field('product', 'field_dimensions', 'string', 'Dimensions', ['max_length' => 255], [], 1, FALSE);

// Currency symbol. The number_decimal formatter reads prefix/suffix from the
// field instance, so this is the single place to change it. Re-asserted on every
// run rather than set at creation, so an existing site picks the change up.
$price = FieldConfig::loadByName('node', 'product', 'field_price');
if ($price && $price->getSetting('prefix') !== '£') {
  $price->setSetting('prefix', '£');
  $price->save();
  echo "  ~ field product.field_price prefix set to £\n";
}

echo "Submission fields\n";
classic_field('submission', 'field_submission_image', 'image', 'Image', [], ['file_directory' => 'submissions/[date:custom:Y]-[date:custom:m]', 'alt_field_required' => TRUE], 1, FALSE);
classic_field('submission', 'field_submission_category', 'entity_reference', 'Category', ['target_type' => 'taxonomy_term'], classic_term_ref(['submission_category']), 1, TRUE);
classic_field('submission', 'field_contributor_name', 'string', 'Your name (as it should appear)', ['max_length' => 128], [], 1, FALSE);
classic_field('submission', 'field_contributor_location', 'string', 'Location', ['max_length' => 128], [], 1, FALSE);

echo "Done.\n";
