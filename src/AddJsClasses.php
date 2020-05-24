<?php

namespace Drupal\facets_block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * AddJsClasses pre-render callback.
 */
class AddJsClasses implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Pre-render callback to add js classes to the facets_block block.
   */
  public static function preRender($elements) {
    $elements['#attributes']['class'][] = 'block-facets-ajax';
    if (empty($elements['#id'])) {
      $elements['#id'] = Html::getUniqueId($elements['#plugin_id']);
    }
    $elements['#attributes']['class'][] = "js-facet-block-id-{$elements['#id']}";

    return $elements;
  }

}
