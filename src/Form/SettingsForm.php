<?php

namespace Drupal\oauth2_server_user_claims\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure OAuth2 Server user claims settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oauth2_server_user_claims_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['oauth2_server_user_claims.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('oauth2_server_user_claims.settings');

    $form['library'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load module CSS library'),
      '#description' => $this->t('Adds basic styles to the user claims table.'),
      '#default_value' => $settings->get('library') ?? TRUE,
      '#return' => TRUE,
    ];

    $form['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional CSS classes'),
      '#default_value' => $settings->get('classes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('oauth2_server_user_claims.settings')
      ->set('library', (bool) $form_state->getValue('library'))
      ->set('classes', $form_state->getValue('classes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
