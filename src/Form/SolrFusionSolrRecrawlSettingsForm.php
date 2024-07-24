<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Additional settings to recrawl data on entity update.
 */
class SolrFusionSolrRecrawlSettingsForm extends ConfigFormBase {

  /**
   * The config factory interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('solr_fusion.recrawl_settings');

    $form['enabled'] = [
      '#type' => 'select',
      '#title' => $this->t('Enable recrawl'),
      '#description' => $this->t('
        Enable recrawl of content when has been change
        by update, delete, or archive.  Recommended to leave set this to "No" on
        the review app.'),
      '#default_value' => $config->get('enabled') ?? 0,
      '#options' => [
        0 => 'No',
        1 => 'Yes',
      ],
      '#required' => TRUE,
    ];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recrawl domain'),
      '#description' => $this->t('
        The recrawl domain.  This is usually the
        public domain ie https://www.solr-fusion.com or
        https://www.preprod.solr-fusion.com.  If using the review app, use the review
        app domain.'),
      '#default_value' => $config->get('domain') ?? '',
      '#required' => TRUE,
    ];

    $form['datasource_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Datasource name'),
      '#description' => $this->t('The datasource used as to recrawl content ie, cms_reindex.'),
      '#default_value' => $config->get('datasource_name') ?? '',
      '#required' => TRUE,
    ];

    $form['query_id'] = [
      '#type' => 'select',
      '#empty_option' => $this->t('- Select -'),
      '#options' => $this->getQueryIdLists(),
      '#title' => $this->t('Query ID'),
      '#description' => $this->t('The query connection to use.'),
      '#default_value' => $config->get('query_id') ?? '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $domain = $form_state->getValue('domain');
    if (!preg_match('/^https?:\/\//', $domain)) {
      $form_state->setErrorByName('domain', $this->t('The domain must start with http:// or https://'));
    }
    if (substr($domain, -1) === '/') {
      $form_state->setErrorByName('domain', $this->t('The domain must not contain a trailing slash at end.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keys = [
      'enabled',
      'domain',
      'datasource_name',
      'query_id',
    ];

    $config = $this->config('solr_fusion.recrawl_settings');
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get the list of query ids.
   *
   * @return array
   *   Return an array of the query id lists.
   */
  protected function getQueryIdLists() {
    $ids = [];
    // Get all the query ids.
    $solrQueryIds = $this->configFactory->listAll('solr_fusion.solr_query');

    foreach ($solrQueryIds as $id) {
      if (strpos($id, 'solr_fusion.solr_query_connector.') === 0) {
        $queryConnectorId = substr($id, 31);
        $ids[$queryConnectorId] = $queryConnectorId;
      }
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['solr_fusion.recrawl_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'solr_fusion_recrawl_settings';
  }

}
