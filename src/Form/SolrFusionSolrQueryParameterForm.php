<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\solr_fusion\Entity\SolrFusionSolrQueryParameter;

/**
 * The configuration form for the Solr query parameter.
 */
class SolrFusionSolrQueryParameterForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\solr_fusion\Entity\SolrFusionSolrQueryParameter */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [SolrFusionSolrQueryParameter::class, 'load'],
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

    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#maxlength' => 255,
      '#default_value' => $entity->value,
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
      $this->messenger()->addMessage($this->t('The query parameter %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('solr_fusion')->notice('The query parameter %label has been updated.', [
        '%label' => $entity->label(),
        'link' => $edit_link,
      ]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('The query parameter %label has been added.', ['%label' => $entity->label()]));
      $this->logger('solr_fusion')->notice('The query parameter %label has been added.', [
        '%label' => $entity->label(),
        'link' => $edit_link,
      ]);
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    // This is here because phpstan gives error if this is missing.
    return 1;
  }

}
