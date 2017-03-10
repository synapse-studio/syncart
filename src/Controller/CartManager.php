<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartEmptyEvent;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartLineItemRemoveEvent;
use Drupal\commerce_cart\Event\CartLineItemUpdateEvent;
use Drupal\commerce_price\Calculator;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Default implementation of the cart manager.
 *
 * Fires its own events, different from the order entity events by being a
 * result of user interaction (add to cart form, cart view, etc).
 */
class CartManager extends ControllerBase {

  /**
   * The order item storage.
   */
  protected $orderItemStorage;


  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CartManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_cart\LineItemMatcherInterface $line_item_matcher
   *   The line item matcher.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct() {
    $entity_type_orderitem = 'commerce_order_item';
    $this->orderItemStorage = \Drupal::entityManager()->getStorage($entity_type_orderitem);
    $this->eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher;
  }


  /**
   * {@inheritdoc}
   */
  public function addOrderItem($cart, $order_item, $combine = TRUE, $save_cart = TRUE) {
    $purchased_entity = $order_item->getPurchasedEntity();
    $quantity = $order_item->getQuantity();
    //dsm($purchased_entity->toArray());
    $matching_order_item = NULL;
    if ($combine) {
      $OrderItemMatcher = new \Drupal\commerce_cart\OrderItemMatcher($this->eventDispatcher);
      $matching_order_item = $OrderItemMatcher->match($order_item, $cart->getItems());
      //$matching_order_item = $this->OrderItemMatch($order_item, $cart->getItems());
    }
    //dsm($matching_order_item);


    $needs_cart_save = FALSE;
    if ($matching_order_item) {
      $new_quantity = Calculator::add($matching_order_item->getQuantity(), $quantity);
      $matching_order_item->setQuantity($new_quantity);
      $matching_order_item->save();
    }
    else {
      $order_item->save();
      $cart->addItem($order_item);
      $needs_cart_save = TRUE;
    }

    $event = new CartEntityAddEvent($cart, $purchased_entity, $quantity, $order_item);
    $this->eventDispatcher->dispatch(CartEvents::CART_ENTITY_ADD, $event);
    if ($needs_cart_save && $save_cart) {
      $cart->save();
    }
    return $order_item;
  }

}
