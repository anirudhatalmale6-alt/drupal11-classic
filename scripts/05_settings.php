<?php

/**
 * @file
 * Step 5 — URL alias patterns, spam protection and site settings.
 *
 * Run with: drush php:script scripts/05_settings.php
 */

use Drupal\pathauto\Entity\PathautoPattern;

/**
 * Creates a pathauto pattern for one node bundle.
 */
function classic_pattern(string $id, string $label, string $pattern, string $bundle): void {
  if (PathautoPattern::load($id)) {
    echo "  = pattern $id\n";
    return;
  }
  $entity = PathautoPattern::create([
    'id' => $id,
    'label' => $label,
    'type' => 'canonical_entities:node',
    'pattern' => $pattern,
    'weight' => 0,
  ]);
  $entity->addSelectionCondition([
    'id' => 'entity_bundle:node',
    'bundles' => [$bundle => $bundle],
    'negate' => FALSE,
    'context_mapping' => ['node' => 'node'],
  ]);
  $entity->save();
  echo "  + pattern $id\n";
}

echo "URL aliases\n";
classic_pattern('article', 'Article', '/journal/[node:title]', 'article');
classic_pattern('product', 'Product listing', '/catalogue/[node:title]', 'product');
classic_pattern('submission', 'User entry', '/community/[node:title]', 'submission');
classic_pattern('page', 'Basic page', '/[node:title]', 'page');

// Taxonomy term aliases.
if (!PathautoPattern::load('term')) {
  PathautoPattern::create([
    'id' => 'term',
    'label' => 'Taxonomy term',
    'type' => 'canonical_entities:taxonomy_term',
    'pattern' => '/topics/[term:vocabulary]/[term:name]',
    'weight' => 0,
  ])->save();
  echo "  + pattern term\n";
}

echo "Spam protection\n";
// Honeypot on the public submission form, plus the registration form that
// leads to it. 5 seconds is low enough not to annoy a real visitor.
\Drupal::configFactory()->getEditable('honeypot.settings')
  ->set('protect_all_forms', FALSE)
  ->set('log', TRUE)
  ->set('element_name', 'homepage_url')
  ->set('time_limit', 5)
  ->set('form_settings.user_register_form', TRUE)
  ->set('form_settings.node_submission_form', TRUE)
  ->set('form_settings.user_pass', TRUE)
  ->save();
echo "  honeypot on node_submission_form + user_register_form\n";

echo "Site settings\n";
\Drupal::configFactory()->getEditable('system.site')
  ->set('name', 'Classic')
  ->set('slogan', 'Considered writing, considered things.')
  ->set('page.front', '/node')
  ->save();

// New accounts land in Contributor and need admin approval, so an approved
// account is a deliberate act rather than a side effect of a signup form.
\Drupal::configFactory()->getEditable('user.settings')
  ->set('register', 'visitors_admin_approval')
  ->set('verify_mail', TRUE)
  ->save();
$role_config = \Drupal::configFactory()->getEditable('user.settings');
$role_config->save();

// Trim the "submitted by" line on the visitor type: the contributor name field
// is what should show, not the account name.
\Drupal::configFactory()->getEditable('node.type.submission')
  ->set('display_submitted', FALSE)
  ->save();

// Same for product listings — a catalogue entry has no byline.
\Drupal::configFactory()->getEditable('node.type.product')
  ->set('display_submitted', FALSE)
  ->save();

echo "Menu\n";
// The front page is the Journal listing, so a "Home" item sits next to a
// "Journal" item pointing at the same place. The brand mark is the way home.
// It is a static plugin link from standard.links.menu.yml, not a
// menu_link_content entity, so it is switched off through the link manager;
// the override is stored in the menu_tree table and survives a cache rebuild.
$menu_link_manager = \Drupal::service('plugin.manager.menu.link');
if ($menu_link_manager->hasDefinition('standard.front_page')) {
  $menu_link_manager->updateDefinition('standard.front_page', ['enabled' => 0]);
  echo "  disabled main-menu link: Home\n";
}

echo "Done.\n";
