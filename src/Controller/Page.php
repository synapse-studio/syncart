<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller Page.
 */
class Page extends ControllerBase {

  /**
   * Main Page.
   */
  public function page($cid) {
    $output = [];
    if (is_numeric($cid)) {
      $cartManager = new SynCart(FALSE);
      $cid = $cartManager->cart->id();
      $output[] = [
        '#markup' => $cid,
      ];

    }

    return $output;
  }

  /**
   * F queryProduct.
   */
  public static function queryProduct($variation_id) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product')
      ->condition('status', 1)
      ->sort('nid', 'DESC')
      ->condition('field_product_variation', $variation_id)
      ->range(0, 1);
    $result = $query->execute();
    return array_shift($result);
  }

}
