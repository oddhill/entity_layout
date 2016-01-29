<?php

namespace Drupal\entity_layout\Collection;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

class BlockPluginCollection extends DefaultLazyPluginCollection
{
  /**
   * {@inheritdoc}
   *
   * @return BlockPluginInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * Get all blocks sorted by weight.
   *
   * @return BlockPluginInterface[]
   */
  public function getSortedByWeight() {
    $instances = [];

    /** @var BlockPluginInterface $instance */
    foreach ($this as $instance) {
      $configuration = $instance->getConfiguration();
      $instances[$configuration['uuid']] = $instance;
    }

    uasort($instances, function (BlockPluginInterface $a, BlockPluginInterface $b) {
      $a_config = $a->getConfiguration();
      $a_weight = isset($a_config['weight']) ? $a_config['weight'] : 0;

      $b_config = $b->getConfiguration();
      $b_weight = isset($b_config['weight']) ? $b_config['weight'] : 0;

      if ($a_weight == $b_weight) {
        return strcmp($a->label(), $b->label());
      }

      return $a_weight > $b_weight ? 1 : -1;
    });

    return $instances;
  }
}
