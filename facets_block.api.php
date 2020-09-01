<?php

/**
 * @file
 * Describes hooks and plugins provided by the Facets Block module.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @file
 * Hooks specific to the Facets Block module.
 */

/**
 * Alter the facets array.
 *
 * @param array $facets
 *   The facets array.
 */
function hook_facets_block_facets_alter(array &$facets) {
  $facets[] = [
    'title' => '',
    'content' => Link::fromTextAndUrl(t('Home page'), Url::fromRoute('<front>', [], [
      'query' => ['filter' => 'recent-posts'],
    ])),
  ];
}
