<?php

namespace Drupal\syncart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'syncart_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['syncart.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('syncart.settings');

    $form['syncart'] = [
      '#type' => 'details',
      '#title' => $this->t('Syncart Adder settings'),
      '#open' => TRUE,
    ];
    $form["syncart"]['link'] = array(
      '#title' => $this->t('Display link'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('link'),
    );
    $form['syncart']['form'] = [
      '#title' => $this->t('Display form'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('form'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('syncart.settings');
    $config
      ->set('link', $form_state->getValue('link'))
      ->set('form', $form_state->getValue('form'))
      ->save();
  }

}
