<?php

namespace Drupal\taxonomy_manager\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Tests\TaxonomyTestTrait;

/**
 * All pages of the module are accessible. (Routing and menus are OK)
 *
 * @group taxonomy_manager
 */
class TaxonomyManagerPagesTest extends WebTestBase {
  use TaxonomyTestTrait;
  private $vocabulary;
  private $admin_user;

  protected function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy'));
    $this->vocabulary = $this->createVocabulary();
  }

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('taxonomy_manager');

  /**
   * Configuration page is accessible.
   */
  function testConfigurationPageIsAccessible() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet("admin/config");
    $this->assertResponse(200);
    $this->assertRaw("Advanced settings for the Taxonomy Manager", "The settings page is accessible.");
    $this->drupalLogout();
  }

  /**
   * The page listing vocabularies is accessible.
   */
  function testVocabulariesListIsAccessible() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet("admin/structure");
    $this->assertResponse(200);
    $this->assertRaw("Administer vocabularies with the Taxonomy Manager", "The link to the page listing vocabularies is accessible.");

    $this->drupalGet("admin/structure/taxonomy_manager/voc");
    $this->assertResponse(200);
    $this->assertRaw("Edit vocabulary settings", "The page listing vocabularies is accessible.");
    $this->drupalLogout();
  }

  /**
   * The page with term editing UI is accessible.
   */
  function testTermsEditingPageIsAccessible() {
    $this->drupalLogin($this->admin_user);
    $voc_name = $this->vocabulary->label();
    // check admin/structure/taxonomy_manager/voc/{$new_voc_name}
    $this->drupalGet("admin/structure/taxonomy_manager/voc/$voc_name");
    $this->assertResponse(200);
    $this->assertRaw("Taxonomy Manager - $voc_name", "The taxonomy manager form for editing terms is accessible.");
    $this->drupalLogout();
  }
}
