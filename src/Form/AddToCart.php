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
    $nid = $form_state->getValue("nid");
    $nid = $_POST['nid'];
    $vid = $form_state->getValue("variation-$nid");
    $quantity = 1;

    $cartManager = new SynCart();
    $variation = $cartManager->add($vid);

    $otvet = "";
    $otvet .= "cartAdd:\n";
    $otvet .= "nid: $nid\n";
    $otvet .= "pid: $vid\n";
    $otvet .= "Товар добавлен в " . Link::createFromRoute('вашу корзину', 'commerce_cart.page')->toString();

    return AjaxResult::ajax("cart-$nid", $otvet);
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
    $nid = $extra['nid'];
    $variations = $extra['variations'];
    $form_state->nid = $nid;
    $form_state->variations = $variations;
    $form_state->setCached(FALSE);
    $form["#suffix"] = "<div class='cart-result' id='cart-$nid'></div>";
    $form["nid"] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];
    if (count($variations) == 1) {
      $vid = array_shift($variations);
      $form["variation-$nid"] = [
        '#type' => 'hidden',
        '#value' => $vid,
      ];
      $form["cart-add-$nid-$vid"] = AjaxResult::button('::cartAdd', 'Добавить в корзину');
    }
    elseif (count($variations) > 1) {
      $options = [];
      foreach ($variations as $key => $value) {
        $variation = \Drupal::entityManager()->getStorage('commerce_product_variation')->load($value);
        $price = $variation->getPrice();
        $price_human = number_format($price->getNumber(), 2) . " " . $price->getCurrencyCode();
        $options[$value] = $variation->title->value . " $price_human";
      }
      $form["variation-$nid"] = [
        '#type' => 'select',
        '#options' => $options,
      ];
      $form["cart-add-$nid"] = AjaxResult::button('::cartAdd', 'Добавить в корзину');
    }

    return $form;
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
