<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @see \Drupal\Core\Form\FormBase
 */
class CartLink extends ControllerBase {

  /**
   * Add to cart.
   */
  public function additem($nid, $pid) {
    // Make sure you don't trust the URL to be safe! Always check for exploits.
    if (is_numeric($pid)) {

      $quantity = 1;
      if(!empty($_POST['count'])) $quantity = $_POST['count'];

      $add = \Drupal\syncart\Controller\CartAdder::AddToCart($nid, $pid, $quantity);
      //drupal_set_message('Добавлено в корзину');
      //$response = new \Symfony\Component\HttpFoundation\RedirectResponse('/node/' . $nid);
      //$response->send();
    }else{
      throw new AccessDeniedHttpException();
    }

    $html['#theme'] = 'answer-card-add';
    $html['#data'] = $add;
    $html['#data']['title'] = '';
    //$output = drupal_render($html);

    $block_load = \Drupal\block\Entity\Block::load('views_block__commerce_cart_form_block_2');
    $block_cart = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block_load);

    $output['block'] = drupal_render($html);
    $output['block_cart'] = drupal_render($block_cart);

    return new JsonResponse($output);

  }

}
