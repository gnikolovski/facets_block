<?php

namespace Drupal\facets_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Template\Attribute;
use Drupal\facets\FacetInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Facets Block' block.
 *
 * @Block(
 *  id = "facets_block",
 *  admin_label = @Translation("Facets Block"),
 * )
 */
class FacetsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use UncacheableDependencyTrait;

  /**
   * The Default Facet Manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * The Module Handler Interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Block Manager Interface.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * The Account Proxy Interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * FacetsBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facets_manager
   *   The Facets manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Block\BlockManagerInterface $plugin_manager_block
   *   The Plugin manager block.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DefaultFacetManager $facets_manager, ModuleHandlerInterface $module_handler, BlockManagerInterface $plugin_manager_block, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->facetsManager = $facets_manager;
    $this->moduleHandler = $module_handler;
    $this->pluginManagerBlock = $plugin_manager_block;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('facets.manager'),
      $container->get('module_handler'),
      $container->get('plugin.manager.block'),
      $container->get('current_user')
    );
  }

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

    $form['block_settings']['hide_empty_block'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide empty block'),
      '#description' => $this->t("Don't render the Facets Block if no facets are available (for instance when no search results are found)."),
      '#default_value' => isset($this->configuration['hide_empty_block']) ? $this->configuration['hide_empty_block'] : FALSE,
    ];

    $form['block_settings']['add_js_classes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add JS classes for Facets block'),
      '#default_value' => $this->configuration['add_js_classes'] ?? FALSE,
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
   *   An array of enabled facets.
   */
  protected function getAvailableFacets() {
    $enabled_facets = $this->facetsManager->getEnabledFacets();
    uasort($enabled_facets, [$this, 'sortFacetsByWeight']);

    $available_facets = [];

    if ($this->moduleHandler->moduleExists('facets_summary')) {
      $available_facets['facets_summary_block:summary'] = $this->t('Summary');
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
   * @param \Drupal\facets\FacetInterface $a
   *   A facet.
   * @param \Drupal\facets\FacetInterface $b
   *   A facet.
   *
   * @return int
   *   Sort value.
   */
  protected function sortFacetsByWeight(FacetInterface $a, FacetInterface $b) {
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
    $this->configuration['show_title'] = $form_state->getValue([
      'block_settings',
      'show_title',
    ]);
    $this->configuration['exclude_empty_facets'] = $form_state->getValue([
      'block_settings',
      'exclude_empty_facets',
    ]);
    $this->configuration['hide_empty_block'] = $form_state->getValue([
      'block_settings',
      'hide_empty_block',
    ]);
    $this->configuration['facets_to_include'] = $form_state->getValue([
      'block_settings',
      'facets_to_include',
    ]);
    $this->configuration['add_js_classes'] = $form_state->getValue([
      'block_settings',
      'add_js_classes',
    ]);
  }

  /**
   * Builds facets.
   *
   * @param array $facets_to_include
   *   A list of facets to display.
   *
   * @return array
   *   An array of facets.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildFacets(array $facets_to_include) {
    $facets = [];

    $available_facets = $this->getAvailableFacets();

    foreach ($available_facets as $plugin_id => $facet_title) {
      if (isset($facets_to_include[$plugin_id]) && $facets_to_include[$plugin_id] === $plugin_id) {
        $block_plugin = $this->pluginManagerBlock->createInstance($plugin_id, []);

        if ($block_plugin && $block_plugin->access($this->currentUser)) {
          $build = $block_plugin->build();

          $exclude_empty_facets = !isset($this->configuration['exclude_empty_facets']) ? TRUE : $this->configuration['exclude_empty_facets'];

          // Skip empty facets.
          $is_empty = FALSE;

          if (!$build) {
            $is_empty = TRUE;
          }
          elseif (isset($build[0]['#attributes']['class']) && in_array('facet-empty', $build[0]['#attributes']['class'])) {
            $is_empty = TRUE;
          }
          // Check if Summary Facet is empty.
          elseif (isset($build['#items']) && count($build['#items']) == 0) {
            $is_empty = TRUE;
          }

          if ($exclude_empty_facets && $is_empty) {
            continue;
          }

          if (empty($build['#attributes'])) {
            $build['#attributes'] = [];
          }

          $facets[] = [
            'title' => $facet_title,
            'content' => $build,
            'attributes' => new Attribute($build['#attributes']),
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
    $facets = $this->buildFacets($facets_to_include);

    // Allow other modules to alter the facets array.
    $this->moduleHandler->alter('facets_block_facets', $facets);

    return [
      '#theme' => 'facets_block',
      '#show_title' => $show_title,
      '#facets' => $facets,
    ];
  }

}
