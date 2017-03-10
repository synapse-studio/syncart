<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Session\Session;


use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\taxonomy\Entity\Term;
/**
 * @see \Drupal\Core\Form\FormBase
 */
class CartAdder extends ControllerBase {

  /**
   * Add to cart.
   */
  public static function AddToCart($nid, $pid, $quantity) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    if (is_numeric($nid) && is_numeric($pid)) {

      // Текущяя сессия корзины
      $account = \Drupal::currentUser();
      $session = \Drupal::request()->getSession();
      $cartSession = new \Drupal\commerce_cart\CartSession($session);

      // Вариация товара, которую добавляем в корзину
      $entity_type_variation = 'commerce_product_variation';
      $product_variation = \Drupal::entityManager()->getStorage($entity_type_variation)->load($pid);

      // Получаем текущую корзину
      $CartProvider = new \Drupal\syncart\Controller\CartProvider($cartSession);
      $cart = $CartProvider->getCart($account);


      // Если корзины нет - создаём новую
      if(!$cart) {
        $cid = 'корзины нет';
        $cart = $CartProvider->createCart($account);
        $cid = $cart->id();
        if ($account->isAnonymous()) {
          $cartSession->addCartId($cart->id());
        }
      }

      // OrderItem - строчка корзины
      $entity_type_lineitem = 'commerce_order_item';
      $OrderItemStorage = \Drupal::entityManager()->getStorage($entity_type_lineitem);
      //$quantity = 1;
      $order_item = $OrderItemStorage->createFromPurchasableEntity($product_variation, [
        'quantity' => $quantity,
      ]);

      // Добавление товара в корзину
      $CartManager = new \Drupal\syncart\Controller\CartManager();
      $CartManager -> addOrderItem($cart, $order_item);

      $otvet   = '';
      $otvet  .= "CartAdd:\n";
      $otvet  .= "nid:" . $nid."\n";
      $otvet  .= "pid:" . $pid."\n";
      $otvet  .= "cart:" . $cart->id()."\n";

      //$otvet  .= 'sess:' . $session->getId() . "\n";
      //$otvet  .= 'carts:' . print_r($cart, true) . "\n";
      //$otvet  .= 'Товар добавлен в ' . Link::createFromRoute('вашу корзину', 'commerce_cart.page')->toString();

      $node = node_load($nid);
      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid]);
      $result['node_link'] = \Drupal::l($node->getTitle(), $url);

      if($node->get('field_product_brand_term')->target_id) {
        $term = Term::load($node->get('field_product_brand_term')->target_id);
        $result['brend'] = $term->getName();
      } else $result['brend'] = '';


      $result['cart_link'] = Link::createFromRoute('Перейти в корзину', 'commerce_cart.page')->toString();


      return $result;
    } else {
      return false;
    }
  }
}
