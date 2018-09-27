<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\block\Entity\Block;

/**
 * CartLink.
 */
class CartLink extends ControllerBase {

  /**
   * Add to cart.
   */
  public function additem($nid, $pid) {
    $ajax = \Drupal::request()->request->get('_drupal_ajax');
    if ($ajax && is_numeric($pid)) {

      $data = CartAdder::addWithLinks($nid, $pid);

      $html['#theme'] = 'answer-card-add';
      $html['#data'] = $data;
      $html['#data']['title'] = '';

      $output['block'] = drupal_render($html);

      $block_load = Block::load('views_block__commerce_cart_form_block_1');
      if ($block_load) {
        $block_cart = \Drupal::entityTypeManager()
          ->getViewBuilder('block')
          ->view($block_load);
        $output['block_cart'] = drupal_render($block_cart);
      }

      return new JsonResponse($output);
    }
    else {
      throw new AccessDeniedHttpException();
    }

  }

}
