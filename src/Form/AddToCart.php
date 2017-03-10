<?php

/**
 * @file
 * Contains Drupal\node_app\Form\RestConteiner.
 */

namespace Drupal\syncart\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;


use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
/**
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class AddToCart extends FormBase {

  /**
   * Session.
   */
  protected $cartSession;


  /**
   * AJAX Responce
   */
  public static function Ajax($otvet, $debug = false){
    $output  = '';
    if($debug){
      $output .= '<pre>';
      $output .= $otvet;
      $output .= '</pre>';
    }else{
      $output .= $otvet;
    }

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#cart-results', $output));
    return $response;
  }
  /**
   * Nginx mkdir
   */
  public function CartAdd(array &$form, FormStateInterface $form_state) {
  	$cart = $form_state->cart;
    $pid = $cart['pid'];
    $nid = $cart['nid'];
    /** @var \Drupal\commerce_order\Entity\LineItemInterface $line_item */
    $account = \Drupal::currentUser();
    if ($account->isAnonymous()) {
      $session = \Drupal::request()->getSession();
      //dsm($session);
      $this->session = $session;
      $this->cartSession = new \Drupal\commerce_cart\CartSession($session);
      $this->cart = false;
    }
    $entity_type_variation = 'commerce_product_variation';
    $product_variation = \Drupal::entityManager()->getStorage($entity_type_variation)->load($pid);


    $CartProvider = new \Drupal\syncart\Controller\CartProvider($this->cartSession);
    $cart = $CartProvider->getCart($account);
    if(!$cart){
      $cid = 'корзины нет';
      $cart = $CartProvider->createCart($account);
      $cid = $cart->id();
      if ($account->isAnonymous()) {
        $this->cartSession->addCartId($cart->id());
        $this->cart = $cid;
        //dsm($cart->id());
      }
    }

    $entity_type_lineitem = 'commerce_line_item';
    $lineItemStorage = \Drupal::entityManager()->getStorage($entity_type_lineitem);
    $quantity = 1;
    $line_item = $lineItemStorage->createFromPurchasableEntity($product_variation, [
        'quantity' => $quantity,
        // @todo Remove once the price calculation is in place.
        'unit_price' => $product_variation->price,
    ]);

    //dsm($line_item);


    $CartManager = new \Drupal\syncart\Controller\CartManager();
    $CartManager -> addLineItem($cart, $line_item);


    /*drupal_set_message($this->t('@entity added to @cart-link.', [
      '@entity' => $purchased_entity->label(),
      '@cart-link' => Link::createFromRoute($this->t('your cart', [], ['context' => 'cart link']), 'commerce_cart.page')->toString(),
    ]));*/

  	$otvet  .= "CartAdd:\n";
    $otvet  .= "nid:" . $nid."\n";
    $otvet  .= "pid:" . $pid."\n";
    $otvet  .= "cart:" . $cart->id()."\n";
    $otvet = '';
    //$otvet  .= 'sess:' . $session->getId() . "\n";
    //$otvet  .= 'carts:' . print_r($cart, true) . "\n";
    $otvet  .= 'Товар добавлен в ' . Link::createFromRoute($this->t('вашу корзину', [], ['context' => 'cart link']), 'commerce_cart.page')->toString();
  	return $this->Ajax($otvet, false);
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
  public function buildForm(array $form, FormStateInterface $form_state,  $extra = NULL) {

    $cart = $extra;
    $form_state->cart = $cart;
    //dsm($app);
    //dsm($this->session->getId());
    //dsm($this->cartSession->getCartIds());
    $form_state->setCached(FALSE);
    $form['cart-add'] = [ // /stop
    		'#type' => 'submit',
        '#suffix' => '<div id="cart-results"></div>',
    		'#value' => $this->t('Добавить в корзину'),
    		'#attributes' => ['class' => ['btn', 'btn-xs', 'btn-warning'],],
    		'#ajax'   => [
    				'callback' => '::CartAdd',
    				'effect'   => 'fade',
    				'progress' => ['type' => 'throbber', 'message' => "",]
    		]
    ];

    return $form;
  }




  /**
   * Implements form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state -> setRebuild(true);
  }
}
