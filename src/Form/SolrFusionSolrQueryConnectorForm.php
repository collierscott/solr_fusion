<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\solr_fusion\Entity\SolrFusionSolrQueryConnector;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The configuration form for the Solr connector.
 */
class SolrFusionSolrQueryConnectorForm extends EntityForm {

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Create the form.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A container interface.
   *
   * @return \Drupal\solr_fusion\Form\SolrFusionSolrQueryConnectorForm
   *   The query connector form.
   */
  public static function create(ContainerInterface $container): SolrFusionSolrQueryConnectorForm {
    $configFactory = $container->get('config.factory');
    return new SolrFusionSolrQueryConnectorForm($configFactory);
  }

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $configFactory = $this->configFactory;

    $queries = [];
    $connectors = [];

    $queryConfigs = $configFactory->listAll('solr_fusion.solr_query.');
    $connectorConfigs = $configFactory->listAll('solr_fusion.solr_connector');

    foreach ($queryConfigs as $queryConfig) {
      $config = $configFactory->get($queryConfig);
      $id = $config->get('id');
      $label = $config->get('label');

      if (!array_key_exists($id, $queries)) {
        $queries[$id] = $label . ' (' . $id . ')';
      }
    }

    foreach ($connectorConfigs as $connectorConfig) {
      $config = $configFactory->get($connectorConfig);
      $id = $config->get('id');
      $label = $config->get('label');

      if (!array_key_exists($id, $connectors)) {
        $connectors[$id] = $label . ' (' . $id . ') [' .
          $config->get('service') . ']';
      }
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server Name/Id'),
      '#default_value' => $entity->label(),
      '#description' => $this->t('The name of the query connector. The machine name should match the bundle name from the url.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#description' => $this->t('This should match the bundle name from the url.'),
      '#machine_name' => [
        'exists' => [SolrFusionSolrQueryConnector::class, 'load'],
      ],
    ];

    $form['connector'] = [
      '#type' => 'select',
      '#title' => $this->t('Connector'),
      '#description' => $this->t('The connector to use for the query.'),
      '#default_value' => $entity->connector ?? '',
      '#options' => $connectors,
    ];

    $form['query'] = [
      '#type' => 'select',
      '#title' => $this->t('Query'),
      '#description' => $this->t('The query.'),
      '#default_value' => $entity->query ?? '',
      '#options' => $queries,
    ];

    return $form;
  }

  /**
   * Save the form.
   *
   * @param array $form
   *   The form to use.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   *
   * @return int
   *   Return an int or a void.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Malformed entity exception.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Entity storage exception.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $entity->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $entity->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('The query %label has been updated to use %conn.', [
        '%label' => $entity->label(),
        '%conn' => $entity->connector,
      ]));
      $this->logger('solr_fusion')->notice('The query %label has been updated to use %conn.', [
        '%label' => $entity->label(),
        '%conn' => $entity->connector,
        'link' => $edit_link,
      ]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('The connector %label has been added to use %conn.', [
        '%label' => $entity->label(),
        '%conn' => $entity->connector,
      ]));
      $this->logger('solr_fusion')->notice('The connector %label has been added to use %conn.', [
        '%label' => $entity->label(),
        '%conn' => $entity->connector,
        'link' => $edit_link,
      ]);
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    // This is here because phpstan gives error if this is missing.
    return 1;
  }

}
