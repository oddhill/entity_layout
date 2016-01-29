<?php

namespace Drupal\entity_layout\Entity;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_layout\EntityLayoutBlockInterface;

/**
 * Defines the node entity class.
 *
 * @ContentEntityType(
 *   id = "entity_layout_block",
 *   label = @Translation("Entity layout block"),
 *   bundle_label = @Translation("Entity layout block"),
 *   base_table = "entity_layout_block",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "bundle" = "layout",
 *   },
 *   bundle_entity_type = "entity_layout"
 * )
 */
class EntityLayoutBlock extends ContentEntityBase implements EntityLayoutBlockInterface {

  /**
   * The block instance if the config has been retrieved once.
   *
   * @var null|BlockPluginInterface
   */
  protected $block_instance = NULL;

  /**
   * The block label is used as the entity label.
   *
   * @return string
   */
  public function label() {
    return $this->getBlock()->label();
  }

  /**
   * Get the entity layout (bundle) of this block content.
   *
   * @return mixed|string
   */
  public function getLayout() {
    return $this->bundle();
  }

  /**
   * Get the block instantiated as a block plugin.
   *
   * @return BlockPluginInterface
   */
  public function getBlock() {
    if (!$this->block_instance) {
      /** @var BlockManagerInterface $block_manager */
      $block_manager = \Drupal::service('plugin.manager.block');
      $configuration = $this->get('config')->first()->getValue();
      $this->block_instance = $block_manager->createInstance($configuration['id'], $configuration);
    }

    return $this->block_instance;
  }

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
  public function updateBlock(array $configuration) {
    $block = $this->getBlock();

    $existing_configuration = $block->getConfiguration();
    $block->setConfiguration($configuration + $existing_configuration);

    $this->block_instance = $block;

    return $block;
  }

  /**
   * Set a block configuration to this entity layout block.
   *
   * @param BlockPluginInterface $block
   *   The block plugin to save configuration for.
   *
   * @return $this
   */
  public function setBlock(BlockPluginInterface $block) {
    $this->block_instance = $block;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Make sure any configuration changes will be persisted.
    $configuration = $this->getBlock()->getConfiguration();

    // Make sure the block uuid is the same as the entity uuid. This makes it
    // easier to update block configuration since you can rely on the entity
    // uuid value.
    if (!isset($configuration['uuid']) || $configuration['uuid'] !== $this->uuid()) {
      $configuration['uuid'] = $this->uuid();
    }

    $this->set('config', $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity layout block ID'))
      ->setDescription(t('The entity layout block ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setDescription(t('The UUID of the Contact entity.'))
      ->setReadOnly(TRUE);

    $fields['layout'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Layout'))
      ->setDescription(t('The entity layout this block belongs to.'))
      ->setSetting('target_type', 'entity_layout')
      ->setReadOnly(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Entity ID.'))
      ->setDescription(new TranslatableMarkup('The ID of the entity this block has been placed for.'))
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entity type'))
      ->setDescription(new TranslatableMarkup('The entity type to which this block is attached.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    $fields['config'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Config'))
      ->setDescription(t('The serialized block configuration.'));

    return $fields;
  }


}
