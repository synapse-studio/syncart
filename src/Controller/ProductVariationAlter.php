<?php

namespace Drupal\syncart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * CartLink.
 */
class ProductVariationAlter extends ControllerBase {

  /**
   * Init.
   */
  public static function init(array &$build, NodeInterface $node) {
    $node_type = $node->getType();
    if ($node_type == 'tovar' && isset($build['field_tovar_variation'])) {
      $renderable = [];
      $variations_values = $node->field_tovar_variation->getValue();
      if (!empty($variations_values)) {
        $variations = self::vatiationsKeys($variations_values);
        $renderable = self::variationRenderable($node->id(), $variations);
      }
      $build['field_tovar_variation'] = $renderable;
    }
  }

  /**
   * Get Cart.
   */
  public static function variationRenderable($nid, $variations) {
    $result = [];
    $config = \Drupal::config('syncart.settings');
    if ($config->get('link')) {
      $result["cartlink-$nid"] = self::getLinks($nid, $variations);
    }
    if ($config->get('form')) {
      $extra = [
        'nid' => $nid,
        'variations' => $variations,
      ];
      $result["cartform-$nid"] = [
        'form' => \Drupal::formBuilder()->getForm('Drupal\syncart\Form\AddToCart', $extra),
      ];
    }
    return $result;
  }

  /**
   * Find tartet IDs.
   */
  public static function vatiationsKeys($variations) {
    $keys = [];
    foreach ($variations as $key => $value) {
      if (isset($value['target_id'])) {
        $keys[$key] = $value['target_id'];
      }
    }
    return $keys;
  }

  /**
   * Get link.
   */
  public static function getLinks($nid, $variations) {
    $result = [];
    $options = ['attributes' => ['class' => ['use-ajax', 'syncart-link']]];
    foreach ($variations as $key => $vid) {
      $extra = [
        'nid' => $nid,
        'pid' => $vid,
      ];
      $url = Url::fromRoute('cartadd', $extra);
      $url->setOptions($options);
      $result['cartlink-' . $vid]['link'] = [
        '#markup' => Link::fromTextAndUrl('В корзину', $url)->toString(),
        '#prefix' => "<div>",
        '#suffix' => "</div>",
      ];
    }
    return $result;
  }

}
