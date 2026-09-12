<?php

/**
 * @file
 * Removes everything scripts/07_sample_content.php created.
 *
 * Content, taxonomy terms, files and the two demo accounts go; the content
 * types, fields, views, workflows and roles all stay.
 *
 * Run with: drush php:script scripts/99_purge_sample_content.php
 */

use Drupal\file\Entity\File;

$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$user_storage = \Drupal::entityTypeManager()->getStorage('user');

echo "Nodes\n";
$nodes = $node_storage->loadByProperties(['type' => ['article', 'product', 'submission', 'page']]);
$node_storage->delete($nodes);
echo "  deleted " . count($nodes) . "\n";

echo "Taxonomy terms\n";
$terms = $term_storage->loadByProperties([
  'vid' => ['article_category', 'product_category', 'submission_category', 'tags'],
]);
$term_storage->delete($terms);
echo "  deleted " . count($terms) . "\n";

echo "Sample files\n";
$deleted = 0;
foreach (File::loadMultiple() as $file) {
  if (str_starts_with($file->getFileUri(), 'public://sample/')) {
    $file->delete();
    $deleted++;
  }
}
echo "  deleted $deleted\n";

echo "Demo accounts\n";
$removed = 0;
foreach (['Margaret Ashcombe', 'Tomas Iversen'] as $name) {
  if ($account = user_load_by_name($name)) {
    $user_storage->delete([$account]);
    $removed++;
  }
}
echo "  deleted $removed\n";

echo "Menu links to deleted pages\n";
// A menu_link_content row pointing at a deleted node would 404 in the nav.
$links = \Drupal::entityTypeManager()->getStorage('menu_link_content')
  ->loadByProperties(['menu_name' => 'main']);
$dropped = 0;
foreach ($links as $link) {
  $uri = $link->link->uri;
  if (!str_starts_with($uri, 'entity:node/')) {
    continue;
  }
  if (!$node_storage->load((int) substr($uri, strlen('entity:node/')))) {
    $link->delete();
    $dropped++;
  }
}
echo "  deleted $dropped\n";

echo "Done. Run 'drush cr' next.\n";
