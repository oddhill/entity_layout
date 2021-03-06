<?php

namespace Drupal\entity_layout\Entity;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\entity_layout\Collection\BlockPluginCollection;
use Drupal\entity_layout\EntityLayoutInterface;

/**
 * Defines the Context entity.
 *
 * @ConfigEntityType(
 *   id = "entity_layout",
 *   label = @Translation("Entity layout"),
 *   handlers = {
 *     "access" = "Drupal\entity_layout\EntityLayoutAccess",
 *     "view_builder" = "Drupal\entity_layout\EntityLayoutViewBuilder",
 *     "list_builder" = "Drupal\entity_layout\EntityLayoutListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_layout\Form\EntityLayoutAddForm",
 *       "delete" = "Drupal\entity_layout\Form\EntityLayoutDeleteForm",
 *       "config-edit" = "Drupal\entity_layout\Form\EntityLayoutConfigEditForm",
 *       "content-edit" = "Drupal\entity_layout\Form\EntityLayoutContentEditForm",
 *     }
 *   },
 *   config_prefix = "layout",
 *   admin_permission = "administer entity layouts",
 *   bundle_of = "entity_layout_block",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "name",
 *     "target_entity_type",
 *     "target_bundle",
 *     "allowed_blocks",
 *     "default_blocks",
 *     "settings",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/entity_layout",
 *     "add-form" = "/admin/structure/entity_layout/add",
 *     "delete-form" = "/admin/structure/entity_layout/{entity_layout}/delete"
 *   }
 * )
 */
class EntityLayout extends ConfigEntityBase implements EntityLayoutInterface {

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
  protected $target_entity_type;

  /**
   * Name of the target bundle.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * An array of default blocks that should be rendered with the layout when
   * nothing has been configured at the content entity level.
   *
   * @var array
   */
  protected $default_blocks = [];

  /**
   * An array of blocks that are allowed to be placed as layout for the target
   * entity/bundle combination.
   *
   * @var array
   */
  protected $allowed_blocks = [];

  /**
   * An array of generic settings for this layout.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Contains a temporary collection of blocks after they have been retrieved
   * with the getBlocks method.
   *
   * @var BlockPluginCollection
   */
  protected $blocks_collection;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->target_entity_type . '.' . $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if ($this->label) {
      return $this->label;
    }

    $entity_type = $this->entityTypeManager()->getDefinition($this->getTargetEntityType());

    $label = $entity_type->getLabel();

    if ($this->getTargetBundle() && $this->getTargetBundle() !== $this->getTargetEntityType()) {
      $bundle_entity = $this->entityTypeManager()
        ->getStorage($entity_type->getBundleEntityType())
        ->load($this->getTargetBundle());

      $label = $label . " ({$bundle_entity->label()})";
    }

    return $label;
  }

  /**
   * Get the target entity type.
   *
   * @return string
   */
  public function getTargetEntityType() {
    return $this->target_entity_type;
  }

  /**
   * Get the target bundle.
   *
   * @return string
   */
  public function getTargetBundle() {
    return $this->target_bundle;
  }

  /**
   * Set the allowed blocks.
   *
   * @param array $blocks
   *   An array of allowed blocks keyed by the block plugin id with a value
   *   of 1 (TRUE).
   *
   * @return $this
   */
  public function setAllowedBlocks(array $blocks) {
    $this->allowed_blocks = $blocks;

    return $this;
  }

  /**
   * Get the array of allowed blocks.
   *
   * @return array
   */
  public function getAllowedBlocks() {
    return $this->allowed_blocks;
  }

  /**
   * Helper method to check if a certain block is allowed.
   *
   * @param string $block_id
   *   The block id to check for.
   *
   * @return bool
   */
  public function blockIsAllowed($block_id) {
    return isset($this->allowed_blocks[$block_id]);
  }

  /**
   * Get all default blocks as a collection.
   *
   * @return BlockPluginCollection
   *
   * @todo Remove this code and replace any existing calls with the new one.
   *
   * @deprecated Will remove. Use get default blocks instead.
   */
  public function getBlocks() {
    return $this->getDefaultBlocks();
  }

  /**
   * Get all default blocks as a collection.
   *
   * @return BlockPluginCollection
   */
  public function getDefaultBlocks() {
    if (!$this->blocks_collection) {
      $blockManager = \Drupal::service('plugin.manager.block');
      $this->blocks_collection = new BlockPluginCollection($blockManager, $this->default_blocks);
    }
    return $this->blocks_collection;
  }

  /**
   * Get a block by id.
   *
   * @param string $block_id
   *   The ID of the block to get.
   *
   * @return BlockPluginInterface
   */
  public function getBlock($block_id) {
    return $this->getBlocks()->get($block_id);
  }

  /**
   * Add a new block.
   *
   * @param array $configuration
   */
  public function addBlock(array $configuration) {

    if (isset($configuration['uuid'])) {
      $this->updateBlock($configuration['uuid'], $configuration);
    } else {
      $configuration['uuid'] = $this->uuidGenerator()->generate();
      $this->getBlocks()->addInstanceId($configuration['uuid'], $configuration);
    }

    return $configuration['uuid'];
  }

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
  public function updateBlock($block_id, array $configuration) {
    $existingConfiguration = $this->getBlock($block_id)->getConfiguration();
    $this->getBlocks()->setInstanceConfiguration($block_id, $configuration + $existingConfiguration);

    return $this;
  }

  /**
   * Remove a block from the collection.
   *
   * @param string $block_id
   *   The ID of the block to remove.
   *
   * @return $this
   */
  public function removeBlock($block_id) {
    $this->getBlocks()->removeInstanceId($block_id);

    return $this;
  }

  /**
   * Return an array of all settings for this entity layout.
   *
   * @return array
   */
  public function getSettings() {
    return $this->settings;
  }

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
  public function getSetting($key, $default = NULL) {
    return isset($this->settings[$key])
      ? $this->settings[$key]
      : $default;
  }

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
  public function setSetting($key, $value) {
    $this->settings[$key] = $value;
    return $this;
  }

  /**
   * Check if a setting exists.
   *
   * @param string $key
   *   The setting key to check for.
   *
   * @return bool
   */
  public function hasSetting($key) {
    return isset($this->settings[$key]);
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
      'default_blocks' => $this->getBlocks(),
    ];
  }
}
