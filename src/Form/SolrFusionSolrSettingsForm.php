<?php

namespace Drupal\solr_fusion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SolrFusionSettingsForm.
 */
class SolrFusionSolrSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('solr_fusion.settings');

    $form['omit_header'] = [
      '#type' => 'select',
      '#title' => $this->t('Omit the response header?'),
      '#description' => $this->t('Setting this to Yes will omit the response header coming back from Solr/Fusion.'),
      '#default_value' => $config->get('omit_header') ?? 0,
      '#options' => [
        0 => 'No',
        1 => 'Yes',
      ],
      '#required' => TRUE,
    ];

    $form['debug_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Debug Mode'),
      '#description' => $this->t('Enable debug mode to view additional data to help with debugging. E.g, in the case of an error, view the query that was made to solr/fusion.'),
      '#default_value' => $config->get('debug_mode') ?? 0,
      '#options' => [
        0 => 'Off',
        1 => 'On',
      ],
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('solr_fusion.settings');
    $config->set('omit_header', $form_state->getValue('omit_header'));
    $config->set('debug_mode', $form_state->getValue('debug_mode'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['solr_fusion.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'solr_fusion_settings';
  }

}
