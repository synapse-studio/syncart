<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\cmlparser\Controller\XmlObject;
use Drupal\cmlparser\Controller\XmlParcer;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * Controller routines for page example routes.
 */
class Page extends ControllerBase {

  public function page($cid) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    $output = [];
    if (is_numeric($cid)) {

      $entity_type_order = 'commerce_order';
      $order = \Drupal::entityManager()->getStorage($entity_type_order)->load($cid);
      $output[] = [
        '#markup' => 'order id=' . $cid,
      ];
      dsm($order->toArray());

      foreach ($order -> line_items as $line_item){
        $line_item = $line_item->entity;
        dsm($line_item->toArray());

        $vatiation = $line_item->purchased_entity->entity;
        $vatiation_id = $vatiation->id();
        dsm($vatiation->toArray());


        $product_nid = self::query_product($vatiation_id);
        $product_node = node_load($product_nid);
        dsm($product_node->toArray());

        $output[] = [
          '#markup' => '<br />товар=' .$product_node->title->value . "<br />
          " . " цена:" . print_r($vatiation->price->getValue(), true) . "<br />
          " . " количество:" . $line_item->quantity->value,
        ];
      }

    }


    return $output;
  }

  public static function query_product($variation_id){
    $query = \Drupal::entityQuery('node');
    $query -> condition('type', 'product')
           -> condition('status', 1)
           -> sort('nid', 'DESC')
           -> condition('field_product_variation', $variation_id)
           -> range(0, 1)
    ;
    //dsm($query);

    //$query->addTag('debug');
    //dsm($query);

    $result = $query->execute();

    return array_shift($result);
  }


}
