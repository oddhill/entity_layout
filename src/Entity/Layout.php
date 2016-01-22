<?php

namespace Drupal\entity_layout\Entity;

use Drupal\context\Reaction\Blocks\BlockCollection;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\entity_layout\EntityLayoutInterface;

/**
 * Defines the Context entity.
 *
 *
 *   id = "entity_layout_custom",
 *   label = @Translation("Entity layout"),
 *   handlers = {
 *     "access" = "Drupal\context\Entity\ContextAccess",
 *     "list_builder" = "Drupal\entity_layout\LayoutTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_layout\Form\LayoutTypeAddForm",
 *       "edit" = "Drupal\entity_layout\Form\LayoutTypeEditForm",
 *       "delete" = "Drupal\entity_layout\Form\LayoutTypeDeleteForm",
 *     }
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/entity_layout/{entity_layout}",
 *     "delete-form" = "/admin/structure/entity_layout/{entity_layout}/delete",
 *     "collection" = "/admin/structure/entity_layout",
 *   },
 *   admin_permission = "administer entity layouts",
 *   bundle_of = "entity_layout",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "name",
 *     "entity_type",
 *     "blocks",
 *     "settings",
 *   }
 *
 */
class Layout extends ConfigEntityBase implements EntityLayoutInterface {

  /**
   * Machine name of the entity layout.
   *
   * @var string
   */
  protected $name;

  /**
   * The label of the layout.
   *
   * @var string
   */
  protected $label;

  /**
   * Name of the entity type this layout has been configured for.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * An array of blocks that should be rendered with the layout.
   *
   * @var array
   */
  protected $blocks = [];

  /**
   * An array of settings for this layout.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Contains a temporary collection of blocks.
   *
   * @var BlockCollection
   */
  protected $blocksCollection;

  /**
   * Get all blocks as a collection.
   *
   * @return BlockPluginInterface[]|BlockCollection
   */
  public function getBlocks() {
    if (!$this->blocksCollection) {
      $blockManager = \Drupal::service('plugin.manager.block');
      $this->blocksCollection = new BlockCollection($blockManager, $this->blocks);
    }

    return $this->blocksCollection;
  }

  /**
   * Get a block by id.
   *
   * @param string $blockId
   *   The ID of the block to get.
   *
   * @return BlockPluginInterface
   */
  public function getBlock($blockId) {
    return $this->getBlocks()->get($blockId);
  }

  /**
   * Add a new block.
   *
   * @param array $configuration
   */
  public function addBlock(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getBlocks()->addInstanceId($configuration['uuid'], $configuration);

    return $configuration['uuid'];
  }

  /**
   * Update an existing blocks configuration.
   *
   * @param string $blockId
   *   The ID of the block to update.
   *
   * @param $configuration
   *   The updated configuration for the block.
   *
   * @return $this
   */
  public function updateBlock($blockId, array $configuration) {
    $existingConfiguration = $this->getBlock($blockId)->getConfiguration();
    $this->getBlocks()->setInstanceConfiguration($blockId, $configuration + $existingConfiguration);

    return $this;
  }

  /**
   * @param $blockId
   * @return $this
   */
  public function removeBlock($blockId) {
    $this->getBlocks()->removeInstanceId($blockId);

    return $this;
  }

  /**
   * Gets the plugin collections used by this entity.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections() {
    return [
      'blocks' => $this->getBlocks(),
    ];
  }
}
