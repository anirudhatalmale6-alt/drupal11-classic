<?php

/**
 * @file
 * Step 4 — a View per content type, each with its own page.
 *
 * Run with: drush php:script scripts/04_views.php
 */

use Drupal\views\Entity\View;

/**
 * Builds the standard "published nodes of one type" filter set.
 */
function classic_filters(string $bundle): array {
  return [
    'status' => [
      'id' => 'status',
      'table' => 'node_field_data',
      'field' => 'status',
      'entity_type' => 'node',
      'entity_field' => 'status',
      'plugin_id' => 'boolean',
      'operator' => '=',
      'value' => '1',
      'group' => 1,
    ],
    'type' => [
      'id' => 'type',
      'table' => 'node_field_data',
      'field' => 'type',
      'entity_type' => 'node',
      'entity_field' => 'type',
      'plugin_id' => 'bundle',
      'operator' => 'in',
      'value' => [$bundle => $bundle],
      'group' => 1,
    ],
  ];
}

/**
 * Sort by authored-on date, newest first.
 */
function classic_sort_created(): array {
  return [
    'created' => [
      'id' => 'created',
      'table' => 'node_field_data',
      'field' => 'created',
      'entity_type' => 'node',
      'entity_field' => 'created',
      'plugin_id' => 'date',
      'order' => 'DESC',
    ],
  ];
}

/**
 * Creates (or replaces) a view.
 */
function classic_view(string $id, string $label, string $description, array $displays): void {
  if ($existing = View::load($id)) {
    $existing->delete();
    echo "  ~ replaced view $id\n";
  }
  else {
    echo "  + view $id\n";
  }
  View::create([
    'id' => $id,
    'label' => $label,
    'description' => $description,
    'tag' => 'classic',
    'base_table' => 'node_field_data',
    'base_field' => 'nid',
    'display' => $displays,
  ])->save();
}

// ---------------------------------------------------------------------------
// Journal — articles and blog posts.
// ---------------------------------------------------------------------------
classic_view('classic_journal', 'Journal', 'Articles and blog posts, newest first.', [
  'default' => [
    'id' => 'default',
    'display_title' => 'Default',
    'display_plugin' => 'default',
    'position' => 0,
    'display_options' => [
      'title' => 'Journal',
      'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
      'cache' => ['type' => 'tag', 'options' => []],
      'query' => ['type' => 'views_query', 'options' => []],
      'exposed_form' => ['type' => 'basic', 'options' => ['submit_button' => 'Apply']],
      'pager' => [
        'type' => 'full',
        'options' => ['items_per_page' => 10, 'offset' => 0, 'quantity' => 5],
      ],
      'style' => ['type' => 'default', 'options' => ['row_class' => 'classic-listing__item']],
      'row' => ['type' => 'entity:node', 'options' => ['view_mode' => 'teaser']],
      'filters' => classic_filters('article'),
      'sorts' => classic_sort_created(),
      'empty' => [
        'area_text_custom' => [
          'id' => 'area_text_custom',
          'table' => 'views',
          'field' => 'area_text_custom',
          'plugin_id' => 'text_custom',
          'empty' => TRUE,
          'content' => 'No articles have been published yet.',
        ],
      ],
    ],
  ],
  'page_1' => [
    'id' => 'page_1',
    'display_title' => 'Page',
    'display_plugin' => 'page',
    'position' => 1,
    'display_options' => [
      'path' => 'journal',
      'menu' => ['type' => 'normal', 'title' => 'Journal', 'menu_name' => 'main', 'weight' => 1, 'description' => ''],
    ],
  ],
  'block_1' => [
    'id' => 'block_1',
    'display_title' => 'Latest articles block',
    'display_plugin' => 'block',
    'position' => 2,
    'display_options' => [
      'title' => 'From the journal',
      // Title-only rows: this block sits in a one-third footer column, where a
      // full teaser wraps to one or two words a line.
      'defaults' => ['title' => FALSE, 'pager' => FALSE, 'row' => FALSE, 'style' => FALSE, 'fields' => FALSE],
      'pager' => ['type' => 'some', 'options' => ['items_per_page' => 4, 'offset' => 0]],
      'style' => ['type' => 'html_list', 'options' => ['type' => 'ul', 'wrapper_class' => 'classic-linklist', 'class' => '']],
      'row' => ['type' => 'fields', 'options' => ['inline' => [], 'separator' => '']],
      'fields' => [
        'title' => [
          'id' => 'title',
          'table' => 'node_field_data',
          'field' => 'title',
          'entity_type' => 'node',
          'entity_field' => 'title',
          'plugin_id' => 'field',
          'label' => '',
          'settings' => ['link_to_entity' => TRUE],
        ],
      ],
      'block_description' => 'Latest articles',
    ],
  ],
]);

// ---------------------------------------------------------------------------
// Catalogue — product listings.
// ---------------------------------------------------------------------------
classic_view('classic_catalogue', 'Catalogue', 'Product listings in a responsive grid.', [
  'default' => [
    'id' => 'default',
    'display_title' => 'Default',
    'display_plugin' => 'default',
    'position' => 0,
    'display_options' => [
      'title' => 'Catalogue',
      'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
      'cache' => ['type' => 'tag', 'options' => []],
      'query' => ['type' => 'views_query', 'options' => []],
      'exposed_form' => ['type' => 'basic', 'options' => ['submit_button' => 'Filter', 'reset_button' => TRUE, 'reset_button_label' => 'Show all']],
      'pager' => ['type' => 'full', 'options' => ['items_per_page' => 12, 'offset' => 0, 'quantity' => 5]],
      'style' => [
        'type' => 'grid',
        'options' => [
          'columns' => 3,
          'automatic_width' => TRUE,
          'alignment' => 'horizontal',
          'row_class' => 'classic-grid__item',
          'col_class_default' => TRUE,
          'row_class_default' => TRUE,
        ],
      ],
      'row' => ['type' => 'entity:node', 'options' => ['view_mode' => 'card']],
      'filters' => classic_filters('product') + [
        'field_product_category_target_id' => [
          'id' => 'field_product_category_target_id',
          'table' => 'node__field_product_category',
          'field' => 'field_product_category_target_id',
          // Views data maps this column to the taxonomy filter, which needs a
          // vocabulary; without 'vid' it fatals while calculating dependencies.
          'plugin_id' => 'taxonomy_index_tid',
          'operator' => 'or',
          'value' => [],
          'vid' => 'product_category',
          'type' => 'select',
          'limit' => TRUE,
          'hierarchy' => FALSE,
          'group' => 1,
          'exposed' => TRUE,
          'expose' => [
            'operator_id' => 'field_product_category_target_id_op',
            'label' => 'Category',
            'identifier' => 'category',
            'operator' => 'field_product_category_target_id_op',
            'required' => FALSE,
            'remember' => FALSE,
            'multiple' => FALSE,
          ],
        ],
      ],
      'sorts' => [
        'title' => [
          'id' => 'title',
          'table' => 'node_field_data',
          'field' => 'title',
          'entity_type' => 'node',
          'entity_field' => 'title',
          'plugin_id' => 'standard',
          'order' => 'ASC',
        ],
      ],
      'empty' => [
        'area_text_custom' => [
          'id' => 'area_text_custom',
          'table' => 'views',
          'field' => 'area_text_custom',
          'plugin_id' => 'text_custom',
          'empty' => TRUE,
          'content' => 'Nothing in the catalogue yet.',
        ],
      ],
    ],
  ],
  'page_1' => [
    'id' => 'page_1',
    'display_title' => 'Page',
    'display_plugin' => 'page',
    'position' => 1,
    'display_options' => [
      'path' => 'catalogue',
      'menu' => ['type' => 'normal', 'title' => 'Catalogue', 'menu_name' => 'main', 'weight' => 2, 'description' => ''],
    ],
  ],
  'block_1' => [
    'id' => 'block_1',
    'display_title' => 'Featured strip',
    'display_plugin' => 'block',
    'position' => 2,
    'display_options' => [
      'defaults' => ['pager' => FALSE, 'filters' => FALSE, 'filter_groups' => FALSE],
      'pager' => ['type' => 'some', 'options' => ['items_per_page' => 3, 'offset' => 0]],
      // No exposed category filter on the block: two exposed forms for the same
      // view on one page fight over the query string.
      'filters' => classic_filters('product'),
      'block_description' => 'Featured products',
    ],
  ],
]);

// ---------------------------------------------------------------------------
// Community — published visitor entries only.
// ---------------------------------------------------------------------------
classic_view('classic_community', 'Community', 'Visitor entries that have passed moderation.', [
  'default' => [
    'id' => 'default',
    'display_title' => 'Default',
    'display_plugin' => 'default',
    'position' => 0,
    'display_options' => [
      'title' => 'From our readers',
      'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
      'cache' => ['type' => 'tag', 'options' => []],
      'query' => ['type' => 'views_query', 'options' => []],
      'exposed_form' => ['type' => 'basic', 'options' => ['submit_button' => 'Apply']],
      'pager' => ['type' => 'full', 'options' => ['items_per_page' => 10, 'offset' => 0, 'quantity' => 5]],
      'style' => ['type' => 'default', 'options' => ['row_class' => 'classic-listing__item']],
      'row' => ['type' => 'entity:node', 'options' => ['view_mode' => 'teaser']],
      'filters' => classic_filters('submission'),
      'sorts' => classic_sort_created(),
      'empty' => [
        'area_text_custom' => [
          'id' => 'area_text_custom',
          'table' => 'views',
          'field' => 'area_text_custom',
          'plugin_id' => 'text_custom',
          'empty' => TRUE,
          'content' => 'No entries have been approved yet. Yours could be the first.',
        ],
      ],
    ],
  ],
  'page_1' => [
    'id' => 'page_1',
    'display_title' => 'Page',
    'display_plugin' => 'page',
    'position' => 1,
    'display_options' => [
      'path' => 'community',
      'menu' => ['type' => 'normal', 'title' => 'Community', 'menu_name' => 'main', 'weight' => 3, 'description' => ''],
    ],
  ],
]);

// ---------------------------------------------------------------------------
// Moderation queue — everything waiting on an editor, across both workflows.
// ---------------------------------------------------------------------------
classic_view('classic_moderation_queue', 'Moderation queue', 'Unpublished content waiting for an editorial decision.', [
  'default' => [
    'id' => 'default',
    'display_title' => 'Default',
    'display_plugin' => 'default',
    'position' => 0,
    'display_options' => [
      'title' => 'Moderation queue',
      'access' => ['type' => 'perm', 'options' => ['perm' => 'view any unpublished content']],
      'cache' => ['type' => 'tag', 'options' => []],
      'query' => ['type' => 'views_query', 'options' => []],
      'exposed_form' => ['type' => 'basic', 'options' => ['submit_button' => 'Apply']],
      'pager' => ['type' => 'full', 'options' => ['items_per_page' => 50, 'offset' => 0, 'quantity' => 5]],
      'style' => ['type' => 'table', 'options' => ['row_class' => '', 'default' => 'changed', 'order' => 'desc', 'columns' => [], 'info' => []]],
      'row' => ['type' => 'fields', 'options' => []],
      'fields' => [
        'title' => [
          'id' => 'title',
          'table' => 'node_field_data',
          'field' => 'title',
          'entity_type' => 'node',
          'entity_field' => 'title',
          'plugin_id' => 'field',
          'label' => 'Title',
          'settings' => ['link_to_entity' => TRUE],
        ],
        'type' => [
          'id' => 'type',
          'table' => 'node_field_data',
          'field' => 'type',
          'entity_type' => 'node',
          'entity_field' => 'type',
          'plugin_id' => 'field',
          'label' => 'Type',
        ],
        'moderation_state' => [
          'id' => 'moderation_state',
          'table' => 'node_field_data',
          'field' => 'moderation_state',
          'entity_type' => 'node',
          'entity_field' => 'moderation_state',
          'plugin_id' => 'field',
          'label' => 'State',
        ],
        'name' => [
          'id' => 'name',
          'table' => 'users_field_data',
          'field' => 'name',
          'relationship' => 'uid',
          'entity_type' => 'user',
          'entity_field' => 'name',
          'plugin_id' => 'field',
          'label' => 'Author',
        ],
        'changed' => [
          'id' => 'changed',
          'table' => 'node_field_data',
          'field' => 'changed',
          'entity_type' => 'node',
          'entity_field' => 'changed',
          'plugin_id' => 'field',
          'label' => 'Updated',
          'type' => 'timestamp',
        ],
      ],
      'relationships' => [
        'uid' => [
          'id' => 'uid',
          'table' => 'node_field_data',
          'field' => 'uid',
          'entity_type' => 'node',
          'entity_field' => 'uid',
          'plugin_id' => 'standard',
          'required' => FALSE,
          'admin_label' => 'Author',
        ],
      ],
      'filters' => [
        'status' => [
          'id' => 'status',
          'table' => 'node_field_data',
          'field' => 'status',
          'entity_type' => 'node',
          'entity_field' => 'status',
          'plugin_id' => 'boolean',
          'operator' => '=',
          'value' => '0',
          'group' => 1,
        ],
      ],
      'sorts' => [
        'changed' => [
          'id' => 'changed',
          'table' => 'node_field_data',
          'field' => 'changed',
          'entity_type' => 'node',
          'entity_field' => 'changed',
          'plugin_id' => 'date',
          'order' => 'DESC',
        ],
      ],
      'empty' => [
        'area_text_custom' => [
          'id' => 'area_text_custom',
          'table' => 'views',
          'field' => 'area_text_custom',
          'plugin_id' => 'text_custom',
          'empty' => TRUE,
          'content' => 'Nothing is waiting for review.',
        ],
      ],
    ],
  ],
  'page_1' => [
    'id' => 'page_1',
    'display_title' => 'Page',
    'display_plugin' => 'page',
    'position' => 1,
    'display_options' => [
      'path' => 'admin/content/queue',
      'menu' => ['type' => 'tab', 'title' => 'Moderation queue', 'weight' => 10, 'description' => ''],
    ],
  ],
]);

\Drupal::service('router.builder')->rebuild();
echo "Done.\n";
