<?php

/**
 * @file
 * Contains \Drupal\syncart\Plugin\Block\TotalCartBlock.
 */

// Пространство имён для нашего блока.
// deposit_calculator - это наш модуль.
namespace Drupal\syncart\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Добавляем простой блок с текстом.
 * Ниже - аннотация, она также обязательна.
 *
 * @Block(
 *   id = "total_cart_block", 
 *   admin_label = @Translation("Итог в корзине"),
 * )
 */
class TotalCartBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = '';
    
    $account = \Drupal::currentUser();
    $session = \Drupal::request()->getSession();
    $cartSession = new \Drupal\commerce_cart\CartSession($session);
  
    $CartProvider = new \Drupal\syncart\Controller\CartProvider($cartSession);
    $cart = $CartProvider->getCart($account);
  
    if($cart) {
      $cid = $cart->id();
      
      if (is_numeric($cid)) { 

        $entity_type_order = 'commerce_order';
        $order = \Drupal::entityManager()->getStorage($entity_type_order)->load($cid);
        
        $data = array();
        $total = array();
        $result = array();
        
        foreach ($order -> order_items as $order_item) {
          $order_item = $order_item->entity;

          $vatiation = $order_item->purchased_entity->entity;
          $vatiation_id = $vatiation->id();

          $product_nid = self::query_product($vatiation_id);
          $product_node = node_load($product_nid);
          
          $data[] = [
            'title' => $product_node->title->value,
            'count' => $order_item->quantity->value,
            'price' => $vatiation->price->getValue()[0]['number'], 
            'currency_code' => $vatiation->price->getValue()[0]['currency_code'],
          ];

        }
        
        
        if(!empty($data)) {
          foreach($data as $val) {
            
            if(empty($total[$val['currency_code']]) && ($val['price'] *1) != 0) {
              $total[$val['currency_code']]['count'] = 0;
              $total[$val['currency_code']]['total'] = 0;
            }
            
            if(empty($val['price'] * 1)) {
              if(empty($total['order']['count'])) {
                $total['order']['count'] = 0;
                $total['order']['total'] = 0;  
              }
              
              $total['order']['count'] = $total['order']['count'] + $val['count']; 
                  
            } else {
              $total[$val['currency_code']]['count'] = $total[$val['currency_code']]['count'] + $val['count'];
              $total[$val['currency_code']]['total'] = $total[$val['currency_code']]['total'] + $val['count'] * $val['price'];    
            }
            
            
            
          }
        }
        $result['cid'] = $cid;
        
        foreach($total as $key => $val) {
          if($key == 'RUB') $total[$key]['currency'] = 'руб'; 
          if($key == 'USD') $total[$key]['currency'] = '$';
          if($key == 'EUR') $total[$key]['currency'] = '€';
          if($key == 'order') {
            $total[$key]['total'] = 0;
            $total[$key]['currency'] = 'order';  
          }
          
          $total[$key]['total'] = number_format($total[$key]['total'], 0, ',', ' ');
        }  
        
        
        if(!empty($total)) {
          $result['total'] = $total; 
           
          $output['#theme'] = 'total-cart';     
          $output['#data'] = $result;
          $output = drupal_render($output);    
        }

      } 
    }
    
    $block = [
      '#type' => 'markup',
      '#markup' => $output,
    ];
    
    return $block; 
  } 
  
  
  public static function query_product($variation_id) {
    $query = \Drupal::entityQuery('node');
    $query -> condition('type', 'product')
           -> condition('status', 1)
           -> sort('nid', 'DESC')
           -> condition('field_product_variation', $variation_id)
           -> range(0, 1);

    $result = $query->execute();

    return array_shift($result);
  }

}