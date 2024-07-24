<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\solr_fusion\Entity\SolrFusionSolrConnector;

/**
 * The configuration form for the Solr query.
 */
class SolrFusionSolrQueryForm extends EntityForm {

  /**
   * {@inheritDoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\solr_fusion\Entity\SolrFusionSolrQuery $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query Name'),
      '#default_value' => $entity->label(),
      '#description' => $this->t(
        'The query name'
      ),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [SolrFusionSolrConnector::class, 'load'],
      ],
    ];

    $form['bundle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bundle'),
      '#description' => $this->t('Enter if different from content type. Use * to supress the bundle form query.'),
      '#default_value' => $entity->bundle ?? '',
    ];

    $form['facet_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Facets to be used as parameters.'),
      '#description' => $this->t('List of facets to be used (pipe "|" delimited.)'),
      '#default_value' => $entity->facet_list ?? '',
      '#required' => TRUE,
    ];

    $form['field_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Fields to be returned (fl)'),
      '#description' => $this->t('List of fields to be returned (pipe "|" delimited.)'),
      '#default_value' => $entity->field_list ?? '',
      '#required' => TRUE,
    ];

    $form['sort'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Sorts'),
      '#description' => $this->t(
        'How should the query be sorted. Format: field:sort_direction. Example: dateline:asc<br>
        Multiple sorts should be pipe "|" delimited.
      '),
      '#default_value' => $entity->sort ?? '',
    ];

    $form['boost_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Boost field (bf)'),
      '#description' => $this->t('Field to be boosted'),
      '#default_value' => $entity->boost_field ?? '',
    ];

    $form['filter_query_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of filter queries (fq)'),
      '#description' => $this->t('List of filter queries to be used (pipe "|" delimited)'),
      '#default_value' => $entity->filter_query_list ?? '',
    ];

    $form['query_field_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of query fields (qf) (pipe "|" delimited)'),
      '#default_value' => $entity->query_field_list ?? '',
    ];

    $form['renamed_facet_field'] = [
      '#type' => 'textarea',
      '#title' => $this->t('The Renamed facet field'),
      '#description' => $this->t('List of old facet field to the new one ie
        field_taxonomy_old:taxonomy_new|field_taxonomy_two:taxonomy_two.'),
      '#default_value' => $entity->renamed_facet_field ?? '',
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
      $this->messenger()->addMessage($this->t('Solr Fusion Query %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('solr_fusion')->notice('Solr Fusion Query %label has been updated.', [
        '%label' => $entity->label(),
        'link' => $edit_link,
      ]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('Solr Fusion Query %label has been added.', ['%label' => $entity->label()]));
      $this->logger('solr_fusion')->notice('Solr Fusion Query %label has been added.', [
        '%label' => $entity->label(),
        'link' => $edit_link,
      ]);
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    // This is here because phpstan gives error if this is missing.
    return 1;
  }

}
