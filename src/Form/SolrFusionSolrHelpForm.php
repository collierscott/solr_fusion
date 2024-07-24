<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SolrFusionSolrHelpForm.
 */
class SolrFusionSolrHelpForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('solr_fusion.help');

    $form['help_txt'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $config->get('help_txt')['value'] ?? '',
      '#format' => $config->get('help_txt')['format'] ?? 'type3',
      '#rows' => 20,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('solr_fusion.help');

    $config->set('help_txt', $form_state->getValue('help_txt'));
    $config->save();

    parent::submitForm($form, $form_state);
    $form_state->setRedirect('solr_fusion.help.page');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['solr_fusion.help'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solr_fusion_help';
  }

}
