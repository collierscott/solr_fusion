<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\solr_fusion\Entity\SolrFusionSolrConnector;

/**
 * The configuration form for the Solr connector.
 */
class SolrFusionSolrConnectorForm extends EntityForm {

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server Name/Id'),
      '#default_value' => $entity->label(),
      '#description' => $this->t('The id of the server. This is used get get the correct connector for a query.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [SolrFusionSolrConnector::class, 'load'],
      ],
    ];

    $form['service'] = [
      '#type' => 'select',
      '#title' => $this->t('Service'),
      '#description' => $this->t('The service to use for sending queries.'),
      '#default_value' => $entity->service ?? 'solr',
      '#options' => [
        'solr' => 'solr',
        'fusion' => 'fusion',
      ],
    ];

    $form['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('HTTP protocol'),
      '#description' => $this->t('The HTTP protocol to use for sending queries.'),
      '#default_value' => $entity->scheme ?? 'http',
      '#options' => [
        'http' => 'http',
        'https' => 'https',
      ],
    ];

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The host to use'),
      '#description' => $this->t('The host name or IP of the server to use, e.g. <code>localhost</code> or <code>www.example.com</code>.'),
      '#default_value' => $entity->host ?? '',
      '#required' => TRUE,
    ];

    $form['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server port'),
      '#description' => $this->t('The Solr server is usually port 8983, while Fusion generally uses 8764 by default.'),
      '#default_value' => $entity->port ?? NULL,
      '#required' => FALSE,
    ];

    $usernameAttributes = [];

    if (!empty($entity->username)) {
      $usernameAttributes = ['disabled' => 'disabled'];
    }

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The username to use to log in. An environmental variable should be used. ex. ENV: SOLR_USERNAME'),
      '#default_value' => $entity->username ?? '',
      '#required' => TRUE,
      '#attributes' => $usernameAttributes,
    ];

    $passwordAttributes = [];

    if (!empty($entity->password)) {
      $passwordAttributes = ['disabled' => 'disabled'];
    }

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('The password to use to log in. An environmental variable should be used. ex. ENV: SOLR_PASSWORD'),
      '#default_value' => $entity->password ?? '',
      '#required' => TRUE,
      '#attributes' => $passwordAttributes,
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to use'),
      '#description' => $this->t('The path that identifies the instance to use on the server.'),
      '#default_value' => $entity->path ?? '/',
      '#required' => TRUE,
    ];

    $form['signals_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signals path to use'),
      '#description' => $this->t('The signals path that identifies the instance to use on the server.'),
      '#default_value' => $entity->signals_path ?? '/',
      '#required' => FALSE,
    ];

    $form['collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The Collection to use'),
      '#description' => $this->t('The collection on the server.'),
      '#default_value' => $entity->collection ?? '',
      '#required' => FALSE,
    ];

    $form['app'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The Fusion app'),
      '#description' => $this->t('The Fusion app to use. This value is not used if the service is Solr.'),
      '#default_value' => $entity->app ?? '',
      '#required' => FALSE,
    ];

    $form['core'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr core/Fusion Pipeline'),
      '#description' => $this->t('The name that identifies the Solr core or Fusion pipeline to use on the server.'),
      '#default_value' => $entity->core ?? '',
      '#required' => TRUE,
    ];

    $form['timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Query timeout'),
      '#description' => $this->t('The timeout in seconds for search queries sent to the server.'),
      '#default_value' => $entity->timeout ?? 5,
      '#required' => TRUE,
    ];

    $form['index_timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Index timeout'),
      '#description' => $this->t('The timeout in seconds for indexing requests to the server.'),
      '#default_value' => $entity->index_timeout ?? 5,
      '#required' => TRUE,
    ];

    $form['optimize_timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Optimize timeout'),
      '#description' => $this->t('The timeout in seconds for background index optimization queries on the server.'),
      '#default_value' => $entity->optimize_timeout ?? 10,
      '#required' => TRUE,
    ];

    $form['finalize_timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Finalize timeout'),
      '#description' => $this->t('The timeout in seconds for index finalization queries on a server.'),
      '#default_value' => $entity->finalize_timeout ?? 30,
      '#required' => TRUE,
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
      $this->messenger()->addMessage($this->t('The connector %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('solr_fusion')->notice('The connector %label has been updated.', [
        '%label' => $entity->label(),
        'link' => $edit_link,
      ]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('The connector %label has been added.', ['%label' => $entity->label()]));
      $this->logger('solr_fusion')->notice('The connector %label has been added.', [
        '%label' => $entity->label(),
        'link' => $edit_link,
      ]);
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    // This is here because phpstan gives error if this is missing.
    return 1;
  }

}
