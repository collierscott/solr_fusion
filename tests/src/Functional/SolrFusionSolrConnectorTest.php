<?php

namespace Drupal\Tests\solr_fusion\Functional;

use Drupal\testing\Base\SolrFusionTestBase;
use Drupal\testing\Base\TestableUserRoleInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test for the SolrFusionSolrConnector.
 *
 * @covers \Drupal\solr_fusion\Entity\SolrFusionSolrConnector
 * @group solr_fusion
 * @group solr_fusion_connector
 */
class SolrFusionSolrConnectorTest extends SolrFusionTestBase {

  /**
   * Modules to install for testing.
   *
   * @var array
   */
  protected static array $modules = ['solr_fusion', 'jsonapi'];

  /**
   * The name of the default theme.
   *
   * @var string
   */
  protected string $defaultTheme = 'stable';

  /**
   * The test setup.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Throw an entity storage exception.
   */
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->createAndSaveTestUserWithRoles(
      [
        TestableUserRoleInterface::SITE_ADMIN,
      ],
      $this->randomMachineName() . SolrFusionTestBase::TEST_NAME_SUFFIX,
    );
  }

  /**
   * Test to see if the Solr connector listing page exists.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Throw an expectation exception.
   */
  public function testSolrFusionSolrConnectorPageExists() {
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/solr_fusion/connector');
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

}
