<?php

namespace Drupal\facets_block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * AddCssClasses pre-render callback.
 */
class AddCssClasses implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Pre-render callback to add hidden class to empty facet block.
   */
  public static function preRender($elements) {
    if (empty($elements['#id'])) {
      $elements['#id'] = Html::getUniqueId($elements['#plugin_id']);
    }

    // Hide facets block if facets array is empty.
    if (empty($elements['content']['#facets'])) {
      $elements['#attributes']['class'][] = 'hidden';
    }

    return $elements;
  }

}
