<?php

namespace Drupal\facets_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Facets Block' block.
 *
 * @Block(
 *  id = "facets_block",
 *  admin_label = @Translation("Facets Block"),
 * )
 */
class FacetsBlock extends BlockBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['block_settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Settings',
    ];

    $form['block_settings']['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Facet titles'),
      '#default_value' => isset($this->configuration['show_title']) ? $this->configuration['show_title'] : TRUE,
    ];

    $form['block_settings']['exclude_empty_facets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude empty facets'),
      '#default_value' => isset($this->configuration['exclude_empty_facets']) ? $this->configuration['exclude_empty_facets'] : TRUE,
    ];

    $form['block_settings']['facets_to_include'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Facets to include'),
      '#default_value' => isset($this->configuration['facets_to_include']) ? $this->configuration['facets_to_include'] : [],
      '#options' => $this->getAvailableFacets(),
    ];

    return $form;
  }

  /**
   * Returns a list of available facets.
   *
   * @return array
   */
  protected function getAvailableFacets() {
    /** @var \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager */
    $facets_manager = \Drupal::service('facets.manager');
    $enabled_facets = $facets_manager->getEnabledFacets();
    uasort($enabled_facets, [$this, 'sortFacetsByWeight']);

    $available_facets = [];

    /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('facets_summary')){
      $available_facets['facets_summary_block:summary'] = t('Summary');
    }

    foreach ($enabled_facets as $facet) {
      /** @var \Drupal\facets\Entity\Facet $facet */
      $available_facets['facet_block:' . $facet->id()] = $facet->getName();
    }

    return $available_facets;
  }

  /**
   * Sorts array of objects by object weight property.
   *
   * @param $a
   * @param $b
   *
   * @return int
   */
  protected function sortFacetsByWeight($a, $b) {
    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();

    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['show_title'] = $form_state->getValue(['block_settings', 'show_title']);
    $this->configuration['exclude_empty_facets'] = $form_state->getValue(['block_settings', 'exclude_empty_facets']);
    $this->configuration['facets_to_include'] = $form_state->getValue(['block_settings', 'facets_to_include']);
  }

  /**
   * Builds facets.
   *
   * @param array $facets_to_include
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildFacets(array $facets_to_include) {
    $facets = [];

    $available_facets = $this->getAvailableFacets();

    foreach ($available_facets as $plugin_id => $facet_title) {
      if (isset($facets_to_include[$plugin_id]) && $facets_to_include[$plugin_id] === $plugin_id) {
        /** @var \Drupal\Core\Block\BlockManager $block_manager */
        $block_manager = \Drupal::service('plugin.manager.block');
        /** @var \Drupal\Core\Block\BlockPluginInterface $block_plugin */
        $block_plugin = $block_manager->createInstance($plugin_id, []);

        if ($block_plugin && $block_plugin->access(\Drupal::currentUser())) {
          $build = $block_plugin->build();

          $exclude_empty_facets = !isset($this->configuration['exclude_empty_facets']) ? TRUE : $this->configuration['exclude_empty_facets'];

          // Skip empty facets.
          $is_empty = FALSE;

          if (!$build) {
            $is_empty = TRUE;
          }
          elseif (isset($build[0]['#attributes']['class']) && $build[0]['#attributes']['class'] === 'facet-empty') {
            $is_empty = TRUE;
          }
          // Check if Summary Facet is empty.
          elseif (isset($build['#items']) && count($build['#items']) == 0) {
            $is_empty = TRUE;
          }

          if ($exclude_empty_facets && $is_empty) {
            continue;
          }

          $facets[] = [
            'title' => $facet_title,
            'content' => $block_plugin->build(),
          ];
        }
      }
    }

    return $facets;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $show_title = !isset($this->configuration['show_title']) ? TRUE : $this->configuration['show_title'];
    $facets_to_include = !isset($this->configuration['facets_to_include']) ? [] : $this->configuration['facets_to_include'];

    return [
      '#theme' => 'facets_block',
      '#show_title' => $show_title,
      '#facets' => $this->buildFacets($facets_to_include),
    ];
  }

}
