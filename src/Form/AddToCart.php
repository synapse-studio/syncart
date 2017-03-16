<?php

namespace Drupal\syncart\Form;

/**
 * @file
 * Contains Drupal\syncart\Form\AddToCart.
 */

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Link;
use Drupal\synhelper\Controller\AjaxResult;
use Drupal\syncart\Controller\SynCart;

/**
 * AddToCart.
 */
class AddToCart extends FormBase {

  /**
   * F: cartAdd.
   */
  public function cartAdd(array &$form, FormStateInterface $form_state) {
    $pid = $form_state->cart['pid'];
    $nid = $form_state->cart['nid'];
    $quantity = 1;

    $cartManager = new SynCart();
    $variation = $cartManager->add($pid);

    $otvet = '';
    $otvet .= "cartAdd:\n";
    $otvet .= "nid:" . $nid . "\n";
    $otvet .= "pid:" . $pid . "\n";

    $otvet  .= 'Товар добавлен в ' . Link::createFromRoute('вашу корзину', 'commerce_cart.page')->toString();

    return AjaxResult::ajax('cart-pid-' . $pid, $otvet);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cart_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {
    $cart = $extra;
    $form_state->cart = $cart;
    $form_state->setCached(FALSE);
    $form['#suffix'] = "<div class='cart-result' id='cart-pid-{$cart['pid']}'></div>";
    $form['cart-add'] = AjaxResult::button('::cartAdd', 'Добавить в корзину');
    return $form;
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
