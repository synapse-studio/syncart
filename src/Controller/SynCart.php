<?php

namespace Drupal\syncart\Controller;

/**
 * @file
 * Contains Drupal\syncart\Controller\SynCart.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_cart\CartSession;
use Drupal\commerce_cart\CartProvider;
use Drupal\commerce_cart\CartManager;
use Drupal\commerce_cart\OrderItemMatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * SynCart.
 */
class SynCart extends ControllerBase {
  /**
   * Cart object.
   *
   * @var object
   */
  public $cart;
  public $variation;
  public $orderItem;

  /**
   * Constructs a new Cart.
   */
  public function __construct($initcart = FALSE) {
    $entityTM = $this->entityTypeManager();
    $currentUser = $this->currentUser();
    $session = \Drupal::request()->getSession();
    $cartSession = new CartSession($session);
    // $cartProvider = new CartProvider($entityTM, $currentUser, $cartSession);
    $cartProvider = \Drupal::service('commerce_cart.cart_provider');
    // $eventDispatcher = new EventDispatcher();
    // $orderItemMatcher = new OrderItemMatcher($eventDispatcher);
    // $cartManager = new CartManager($entityTM, $orderItemMatcher, $eventDispatcher);
    $cartManager = \Drupal::service('commerce_cart.cart_manager');

    // Store & cart.
    $order_type = 'default';
    $store = $this->getStore();
    $cart = $cartProvider->getCart($order_type, $store);
    if (!$cart && $initcart) {
      $cart = $cartProvider->createCart($order_type, $store);
    }

    $this->store = $store;
    $this->cart = $cart;
    $this->orderStorage = $entityTM->getStorage('commerce_order');
    $this->variationStorage = $entityTM->getStorage('commerce_product_variation');
    $this->orderItemStorage = $entityTM->getStorage('commerce_order_item');
    $this->cartProvider = $cartProvider;
    $this->cartManager = $cartManager;
    $this->cartManager = $cartManager;

    // Объединяем корзины пользователя если их несколько.
    $carts = $cartProvider->getCarts();
    if (count($carts) > 1) {
      $goods = [];
      foreach ($carts as $key => $otherCart) {
        foreach ($otherCart->getItems() as $cartItem) {
          $otherCart->removeItem($cartItem);
          $pid = $cartItem->get('purchased_entity')->target_id;
          $quantity = $cartItem->get('quantity')->value;
          if (isset($goods[$pid])) {
            $goods[$pid] += $quantity;
          }
          else {
            $goods[$pid] = $quantity;
          }
        }
        $otherCart->save();
        if ($otherCart->id() !== $cart->id()) {
          $otherCart->delete();
        }
      }
      foreach ($goods as $pid => $quantity) {
        $this->add($pid, $quantity);
      }
    }
  }

  /**
   * Add to cart.
   */
  public function add($pid, $quantity = 1) {

    // Вариация товара, которую добавляем в корзину.
    $variation = $this->variationStorage->load($pid);
    $this->variation = $variation;

    // Default $quantity = 1.
    $order_item = $this->orderItemStorage->createFromPurchasableEntity(
      $variation, ['quantity' => $quantity]
    );
    $this->orderItem = $order_item;

    // Add item.
    $this->cartManager->addOrderItem($this->cart, $order_item);

    return $variation;
  }

  /**
   * Add to cart.
   */
  public static function getStore() {
    $query = \Drupal::entityQuery('commerce_store');
    $query->range(0, 1);
    $ids = $query->execute();
    $store_id = array_shift($ids);
    $store = \Drupal::entityManager()->getStorage('commerce_store')->load($store_id);
    return $store;
  }

}
