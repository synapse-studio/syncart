<?php

namespace Drupal\syncart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\synhelper\Controller\AjaxResult;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  /**
   * AJAX Wrapper.
   *
   * @var wrapper
   */
  private $wrapper = 'syncart-settings-result';

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
   * AJAX ajaxPatch.
   */
  public function ajaxPatch(array &$form, $form_state) {
    $otvet = "ajaxPatch:\n";
    $module_handler = \Drupal::service('module_handler');
    $commerce = $module_handler->getModule('commerce_product')->getPath();
    $syncart = $module_handler->getModule('syncart')->getPath();
    $file = DRUPAL_ROOT . "/$commerce/commerce_product.module";
    $patch = DRUPAL_ROOT . "/$syncart/assets/commerce_product.module.diff";
    $command = "patch $file < $patch";
    $otvet .= "$command\n";
    exec($command, $result);
    return AjaxResult::ajax($this->wrapper, $otvet, $result);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('syncart.settings');

    $form['patch'] = [
      '#type' => 'details',
      '#title' => $this->t('Patch Commerce commerce_product.module'),
      '#suffix' => '<div id="' . $this->wrapper . '"></div>',
      'exec'  => AjaxResult::button('::ajaxPatch', 'Patch exec'),
    ];
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
