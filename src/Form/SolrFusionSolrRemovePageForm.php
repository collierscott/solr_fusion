<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\solr_fusion\SolrFusionSolrServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting a document from solr index.
 */
class SolrFusionSolrRemovePageForm extends FormBase {

  /**
   * The Solr service.
   *
   * @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface
   */
  private SolrFusionSolrServiceInterface $solr;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr */
    $solr = $container->get('solr_fusion.solr_service');
    return new static($solr);
  }

  /**
   * The constructor.
   *
   * @param \Drupal\solr_fusion\SolrFusionSolrServiceInterface $solr
   *   The solr service.
   */
  public function __construct(SolrFusionSolrServiceInterface $solr) {
    $this->solr = $solr;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['solr_fusion_page_to_remove'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page URL'),
      '#size' => 110,
      '#maxlength' => 256,
      '#required' => TRUE,
      '#description' => $this->t('Enter the page url to validate and remove from solr index. Example : https://solr-fusion.com/en/page-path'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solr_fusion_remove_a_page';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uri = $form_state->getValue('solr_fusion_page_to_remove');
    if (!(UrlHelper::isValid($uri, TRUE) && preg_match('/^https?:/', $uri))) {
      $form_state->setError($form['solr_fusion_page_to_remove'], 'Page URL must be a valid https URL(Example : https://solr-fusion.com/en/page-path).');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('solr_fusion_page_to_remove');
    $is_deleted = $this->solr->deleteDocument($url);

    if (!empty($is_deleted) && isset($is_deleted['status_code'])) {
      if ($is_deleted['status_code'] === 204) {
        $this->messenger()->addMessage($this->t('Status : %code, The document %url has been deleted from solr index.', [
          '%url' => $url,
          '%code' => $is_deleted['status_code'],
        ]));
      }
      else {
        $this->messenger()->addError($this->t('Status : %code, The document %url has not been deleted from solr index. due to some error', [
          '%url' => $url,
          '%code' => $is_deleted['status_code'],
        ]));
      }
    }
  }

}
