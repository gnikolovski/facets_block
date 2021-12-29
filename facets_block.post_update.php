<?php

/**
 * @file
 * Post update functions for Facets block.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Simplify facets_to_include list.
 */
function facets_block_post_update_simplify_facets_to_include(&$sandbox = NULL) {
  // Check if the block module is enabled first.
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    return;
  }

  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block) {
      if ($block->getPluginId() === 'facets_block') {
        $block_settings = $block->get('settings');
        $block_settings['facets_to_include'] = array_values(array_filter($block_settings['facets_to_include']));
        $block->set('settings', $block_settings);
        return TRUE;
      }
      return FALSE;
    });
}
