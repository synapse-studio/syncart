<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\commerce_cart\Exception\DuplicateCartException;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Default implementation of the cart provider.
 */
class CartProvider extends ControllerBase {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $orderStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * The loaded cart data, keyed by cart order ID, then grouped by uid.
   * @var array
   */
  protected $cartData = [];
  protected $cartid = false;

  /**
   *
   */
  public function __construct($cartSession){
    $entity_type_order = 'commerce_order';
    $this->orderStorage = \Drupal::entityManager()->getStorage($entity_type_order);
    $this->cartSession = $cartSession;
  }

  /**
   * Создать корзину
   */
  public function createCart($account = NULL) {
    $order_type = 'default';
    $store_id = 1;
    $uid = $account->id();
    if ($this->getCartId($account)) {
      // Don't allow multiple cart orders matching the same criteria.
      throw new DuplicateCartException("A cart order for type '$order_type', store '$store_id' and account '$uid' already exists.");
    }
    // Create the new cart order.
    $cart = $this->orderStorage->create([
      'type' => $order_type,
      'store_id' => $store_id,
      'uid' => $uid,
      'cart' => TRUE,
    ]);

    $cart->save();
    // Store the new cart order id in the anonymous user's session so that it
    // can be retrieved on the next page load.
    if ($account->isAnonymous()) {
      $this->cartSession->addCartId($cart->id());
    }
    // Cart data has already been loaded, add the new cart order to the list.
    if (isset($this->cartData[$uid])) {
      $this->cartData[$uid][$cart->id()] = [
        'type' => $order_type,
        'store_id' => $store_id,
      ];
    }

    return $cart;
  }

  /**
   * {@inheritdoc}
   */
  public function getCart($account = NULL) {
    $cart = NULL;
    $cart_id = $this->getCartId($account);
    if ($cart_id) {
      $cart = $this->orderStorage->load($cart_id);
    }

    return $cart;
  }

  /**
   * {@inheritdoc}
   */
  public function getCartId($account = NULL) {
    $cart_id = NULL;
    if($this->cartid){
      $cart_id = $this->cartid;
    }else{
      $cart_data = $this->loadCartData($account);
      if ($cart_data) {
        reset($cart_data);
        $cart_id = key($cart_data);
        $this->cartid = $cart_id;
      }
    }

    return $cart_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarts(AccountInterface $account = NULL) {
    $carts = [];
    $cart_ids = $this->getCartIds($account);
    if ($cart_ids) {
      $carts = $this->orderStorage->loadMultiple($cart_ids);
    }

    return $carts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCartIds(AccountInterface $account = NULL) {
    $cart_data = $this->loadCartData($account);
    return array_keys($cart_data);
  }

  /**
   * Loads the cart data for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If empty, the current user is assumed.
   *
   * @return array
   *   The cart data.
   */
  public function loadCartData(AccountInterface $account = NULL) {
    $account = $account ?: $this->currentUser;
    $uid = $account->id();
    if (isset($this->cartData[$uid])) {
      return $this->cartData[$uid];
    }

    if ($account->isAuthenticated()) {
      $query = $this->orderStorage->getQuery()
        ->condition('cart', TRUE)
        ->condition('uid', $account->id())
        ->sort('order_id', 'DESC');

      $cart_ids = $query->execute();
    }
    else {
      $cart_ids = $this->cartSession->getCartIds();
    }

    $this->cartData[$uid] = [];
    if (!$cart_ids) {
      return [];
    }
    // Getting the cart data and validating the cart ids received from the
    // session requires loading the entities. This is a performance hit, but
    // it's assumed that these entities would be loaded at one point anyway.
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $carts */
    $carts = $this->orderStorage->loadMultiple($cart_ids);
    foreach ($carts as $cart) {
      if ($cart->getCustomerId() != $uid || empty($cart->cart)) {
        // Skip orders that are no longer eligible.
        continue;
      }

      $this->cartData[$uid][$cart->id()] = [
        'type' => $cart->bundle(),
        'store_id' => $cart->getStoreId(),
      ];
    }

    return $this->cartData[$uid];
  }
}
