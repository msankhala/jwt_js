<?php

namespace Drupal\jwt_js\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Jwt Expiry config form.
 */
class JwtExpiryConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jwt_expiry_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jwt_js.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jwt_js.settings');
    $form['expiry_time'] = [
      '#type' => 'textfield',
      '#attributes' => [
        ' type' => 'number',
      ],
      '#title' => $this->t('Enter Token Expiry in Hour'),
      '#default_value' => $config->get('expiry_time'),
      '#required' => TRUE,
      '#maxlength' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('jwt_js.settings')

      // Set the submitted configuration setting.
      ->set('expiry_time', $form_state->getValue('expiry_time'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
