<?php

namespace Drupal\Tests\facets_block\Functional;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Facets Block.
 *
 * @group facets_block
 */
class FacetsBlockTest extends BrowserTestBase {

  use BlockCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'facets_block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer site configuration',
      'access administration pages',
    ]);

    $this->drupalLogin($admin_user);
  }

  /**
   * Test that the block is available.
   */
  public function testBlockAvailability() {
    $this->drupalGet('/admin/structure/block');
    $this->clickLink('Place block');
    $this->assertSession()->pageTextContains('Facets Block');
    $this->assertSession()->linkByHrefExists('admin/structure/block/add/facets_block/', 0);
  }

  /**
   * Test that the block can be placed.
   */
  public function testBlockPlacement() {
    $this->drupalPlaceBlock('facets_block', [
      'region' => 'content',
      'label' => 'Facets Block',
      'id' => 'facetsblock',
    ]);

    $this->drupalGet('admin/structure/block');
    $this->assertSession()->pageTextContains('Facets Block');

    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Facets Block');
  }

}
