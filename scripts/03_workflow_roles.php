<?php

/**
 * @file
 * Step 3 — moderation workflows, roles and permissions.
 *
 * Run with: drush php:script scripts/03_workflow_roles.php
 */

use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

// ---------------------------------------------------------------------------
// Editorial workflow (ships with core) — attach the staff-authored types.
// ---------------------------------------------------------------------------
$editorial = Workflow::load('editorial');
if ($editorial) {
  $plugin = $editorial->getTypePlugin();
  foreach (['article', 'page', 'product'] as $bundle) {
    $plugin->addEntityTypeAndBundle('node', $bundle);
  }
  // The visitor-facing type gets its own workflow below, not this one.
  if ($plugin->appliesToEntityTypeAndBundle('node', 'submission')) {
    $plugin->removeEntityTypeAndBundle('node', 'submission');
  }
  $editorial->save();
  echo "Editorial workflow -> article, page, product\n";
}

// ---------------------------------------------------------------------------
// Community submission workflow.
//
// Separate from Editorial on purpose: a visitor must never be able to reach a
// transition that publishes, and sharing a workflow makes that one checkbox
// away from being true.
// ---------------------------------------------------------------------------
$workflow = Workflow::load('community_submission');
if (!$workflow) {
  $workflow = Workflow::create([
    'id' => 'community_submission',
    'label' => 'Community submission',
    'type' => 'content_moderation',
  ]);
  echo "+ community_submission workflow\n";
}
else {
  echo "= community_submission workflow\n";
}
$plugin = $workflow->getTypePlugin();

// The content_moderation plugin seeds draft + published and the transitions
// between them, so add only what is missing.
$state_labels = [
  'draft' => 'Draft',
  'needs_review' => 'Needs review',
  'published' => 'Published',
  'rejected' => 'Rejected',
];
foreach ($state_labels as $id => $label) {
  if (!$plugin->hasState($id)) {
    $plugin->addState($id, $label);
  }
  $plugin->setStateLabel($id, $label);
}

// 'publish' is seeded by the plugin; 'approve' replaces it so the label an
// editor sees matches what the button actually does.
if ($plugin->hasTransition('publish')) {
  $plugin->deleteTransition('publish');
}
$transitions = [
  'create_new_draft' => ['Create new draft', ['draft'], 'draft'],
  'submit_for_review' => ['Submit for review', ['draft', 'rejected'], 'needs_review'],
  'send_back' => ['Send back to the contributor', ['needs_review', 'published'], 'draft'],
  'approve' => ['Approve and publish', ['draft', 'needs_review', 'rejected'], 'published'],
  // Also covers unpublishing: a live entry moved to Rejected leaves the site,
  // because 'rejected' is the default revision. Workflows refuses a second
  // transition over the same published -> rejected pair, so there is no
  // separate 'unpublish'.
  'reject' => ['Reject / unpublish', ['needs_review', 'published'], 'rejected'],
];
foreach ($transitions as $id => [$label, $from, $to]) {
  if ($plugin->hasTransition($id)) {
    $plugin->setTransitionLabel($id, $label);
    $plugin->setTransitionFromStates($id, $from);
  }
  else {
    $plugin->addTransition($id, $label, $from, $to);
  }
}

$plugin->addEntityTypeAndBundle('node', 'submission');
$workflow->save();

// published / default_revision have no setter on the plugin, so they are written
// straight into type_settings. 'rejected' is default_revision so that pulling a
// live entry down actually takes it off the site.
$config = \Drupal::configFactory()->getEditable('workflows.workflow.community_submission');
$state_settings = [
  'draft' => ['published' => FALSE, 'default_revision' => FALSE, 'weight' => 0],
  'needs_review' => ['published' => FALSE, 'default_revision' => FALSE, 'weight' => 1],
  'published' => ['published' => TRUE, 'default_revision' => TRUE, 'weight' => 2],
  'rejected' => ['published' => FALSE, 'default_revision' => TRUE, 'weight' => 3],
];
foreach ($state_settings as $id => $settings) {
  foreach ($settings as $key => $value) {
    $config->set("type_settings.states.$id.$key", $value);
  }
}
$config->set('type_settings.default_moderation_state', 'draft')->save();

// ---------------------------------------------------------------------------
// Roles.
// ---------------------------------------------------------------------------

/**
 * Creates a role if missing and grants it a set of permissions.
 */
function classic_role(string $id, string $label, int $weight, array $permissions): void {
  $role = Role::load($id);
  if (!$role) {
    $role = Role::create(['id' => $id, 'label' => $label, 'weight' => $weight]);
    echo "+ role $id\n";
  }
  else {
    echo "= role $id\n";
  }
  $available = array_keys(\Drupal::service('user.permissions')->getPermissions());
  foreach ($permissions as $permission) {
    if (!in_array($permission, $available, TRUE)) {
      // Loud on purpose: a permission string that no module defines is silently
      // dropped by Drupal, and the role then looks configured but is not.
      echo "  !! unknown permission, NOT granted: $permission\n";
      continue;
    }
    $role->grantPermission($permission);
  }
  $role->save();
}

// Contributor — a registered visitor who can file entries but never publish.
classic_role('contributor', 'Contributor', 3, [
  'access content',
  'create submission content',
  'edit own submission content',
  'delete own submission content',
  'view own unpublished content',
  'view latest version',
  'use text format restricted_html',
  'use community_submission transition create_new_draft',
  'use community_submission transition submit_for_review',
]);

// Editor — runs the editorial back end. Everything except site configuration.
classic_role('content_editor', 'Content editor', 4, [
  'access content',
  'access content overview',
  'access administration pages',
  'access toolbar',
  'view own unpublished content',
  'view any unpublished content',
  'view all revisions',
  'revert all revisions',
  'view latest version',
  'access files overview',
  'create article content', 'edit any article content', 'delete any article content',
  'create page content', 'edit any page content', 'delete any page content',
  'create product content', 'edit any product content', 'delete any product content',
  'create submission content', 'edit any submission content', 'delete any submission content',
  'create terms in article_category', 'edit terms in article_category',
  'create terms in product_category', 'edit terms in product_category',
  'create terms in submission_category', 'edit terms in submission_category',
  'create terms in tags', 'edit terms in tags',
  'use text format basic_html',
  'use text format restricted_html',
  'access media overview', 'create media', 'update any media', 'delete any media', 'view media',
  'use editorial transition create_new_draft',
  'use editorial transition publish',
  'use editorial transition archive',
  'use editorial transition archived_draft',
  'use editorial transition archived_published',
  'use community_submission transition create_new_draft',
  'use community_submission transition submit_for_review',
  'use community_submission transition approve',
  'use community_submission transition reject',
  'use community_submission transition send_back',
]);

// ---------------------------------------------------------------------------
// Anonymous and authenticated baseline.
// ---------------------------------------------------------------------------
$anonymous = Role::load('anonymous');
foreach (['access content', 'view media', 'search content'] as $permission) {
  $anonymous->grantPermission($permission);
}
// Anonymous submission is OFF by default. Turning it on means granting
// 'create submission content' plus 'use community_submission transition
// submit_for_review' here — see the handover notes before you do.
$anonymous->revokePermission('create submission content');
$anonymous->save();
echo "= anonymous baseline\n";

$authenticated = Role::load('authenticated');
foreach (['access content', 'view media', 'search content', 'view own unpublished content'] as $permission) {
  $authenticated->grantPermission($permission);
}
$authenticated->save();
echo "= authenticated baseline\n";

echo "Done.\n";
