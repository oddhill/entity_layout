<?php

namespace Drupal\entity_layout;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\entity_layout\Collection\BlockPluginCollection;

interface EntityLayoutInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Get the target entity type.
   *
   * @return string
   */
  public function getTargetEntityType();

  /**
   * Get the target bundle.
   *
   * @return string
   */
  public function getTargetBundle();

  /**
   * Set the allowed blocks.
   *
   * @param array $blocks
   *   An array of allowed blocks keyed by the block plugin id with a value
   *   of 1 (TRUE).
   *
   * @return $this
   */
  public function setAllowedBlocks(array $blocks);

  /**
   * Get the array of allowed blocks.
   *
   * @return array
   */
  public function getAllowedBlocks();

  /**
   * Helper method to check if a certain block is allowed.
   *
   * @param string $block_id
   *   The block id to check for.
   *
   * @return bool
   */
  public function blockIsAllowed($block_id);

  /**
   * Get all default blocks as a collection.
   *
   * @return BlockPluginCollection
   *
   * @todo Remove this code and replace any existing calls with the new one.
   *
   * @deprecated Will remove. Use get default blocks instead.
   */
  public function getBlocks();

  /**
   * Get all default blocks as a collection.
   *
   * @return BlockPluginCollection
   */
  public function getDefaultBlocks();

  /**
   * Get a block by id.
   *
   * @param string $block_id
   *   The ID of the block to get.
   *
   * @return BlockPluginInterface
   */
  public function getBlock($block_id);

  /**
   * Add a new block.
   *
   * @param array $configuration
   */
  public function addBlock(array $configuration);

  /**
   * Update an existing blocks configuration.
   *
   * @param string $block_id
   *   The ID of the block to update.
   * @param $configuration
   *   The updated configuration for the block.
   *
   * @return $this
   */
  public function updateBlock($block_id, array $configuration);

  /**
   * Remove a block from the collection.
   *
   * @param string $block_id
   *   The ID of the block to remove.
   *
   * @return $this
   */
  public function removeBlock($block_id);

  /**
   * Return an array of all settings for this entity layout.
   *
   * @return array
   */
  public function getSettings();

  /**
   * Get a setting value by key.
   *
   * @param string $key
   *   Key of the setting to retrieve.
   * @param null $default
   *   Value to return if no setting exists for key.
   *
   * @return null
   */
  public function getSetting($key, $default = NULL);

  /**
   * Set a value to the array of settings.
   *
   * @param string $key
   *   The key of the value to set.
   * @param mixed $value
   *   The value of the setting to set.
   *
   * @return $this
   */
  public function setSetting($key, $value);

  /**
   * Check if a setting exists.
   *
   * @param string $key
   *   The setting key to check for.
   *
   * @return bool
   */
  public function hasSetting($key);

  /**
   * Gets the plugin collections used by this entity.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections();
}
