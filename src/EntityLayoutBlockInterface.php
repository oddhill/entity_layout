<?php

namespace Drupal\entity_layout;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;

interface EntityLayoutBlockInterface extends ContentEntityInterface {

  /**
   * Get the entity layout (bundle) of this block content.
   *
   * @return mixed|string
   */
  public function getLayout();

  /**
   * Get the block instantiated as a block plugin.
   *
   * @return BlockPluginInterface
   */
  public function getBlock();

  /**
   * Update the block instance configuration.
   *
   * @param array $configuration
   *   An array of block configuration to merge with the existing
   *   configuration values.
   *
   * @return BlockPluginInterface
   *   The updated block instance.
   */
  public function updateBlock(array $configuration);

  /**
   * Set a block configuration to this entity layout block.
   *
   * @param BlockPluginInterface $block
   *   The block plugin to save configuration for.
   *
   * @return $this
   */
  public function setBlock(BlockPluginInterface $block);
}
