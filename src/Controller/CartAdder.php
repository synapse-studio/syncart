<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Link;

/**
 * CartAdder.
 */
class CartAdder extends ControllerBase {

  /**
   * Add to cart.
   */
  public static function addWithLinks($nid, $pid, $quantity = 1) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    if (is_numeric($nid) && is_numeric($pid)) {

      $cartManager = new SynCart();
      $variation = $cartManager->add($pid);
      $cid = $cartManager->cart->id();

      $node = Node::load($nid);
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
      $result['node_link'] = \Drupal::l($node->getTitle(), $url);
      $result['cart_link'] = Link::createFromRoute('Перейти в корзину', 'commerce_cart.page')->toString();

      return $result;
    }
    else {
      return FALSE;
    }
  }

}
