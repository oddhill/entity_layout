<?php

namespace Drupal\entity_layout;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\entity_layout\Collection\BlockPluginCollection;
use Drupal\entity_layout\Entity\EntityLayoutBlock;

class EntityLayoutService {

  /**
   * The Drupal entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal block manager.
   *
   * @var BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The Drupal context repository.
   *
   * @var ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The Drupal UUID generator service.
   *
   * @var UuidInterface
   */
  protected $uuid;

  /**
   * EntityLayoutService constructor.
   *
   * @param BlockManagerInterface $blockManager
   * @param ContextRepositoryInterface $contextRepository
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param UuidInterface $uuid
   */
  public function __construct(
    BlockManagerInterface $blockManager,
    ContextRepositoryInterface $contextRepository,
    EntityTypeManagerInterface $entityTypeManager,
    UuidInterface $uuid
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
    $this->uuid = $uuid;
  }

  /**
   * Get the label for the target entity.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout object to get the target entity label for.
   *
   * @return string
   */
  public function getTargetEntityLabel(EntityLayoutInterface $entity_layout) {
    return $this->entityTypeManager
      ->getDefinition($entity_layout->getTargetEntityType())
      ->getLabel();
  }

  /**
   * Get the bundle label for the target bundle.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout object to get the target bundle label for.
   *
   * @return null|string
   */
  public function getTargetBundleLabel(EntityLayoutInterface $entity_layout) {
    return $this->entityTypeManager
      ->getDefinition($entity_layout->getTargetEntityType())
      ->getBundleLabel();
  }

  /**
   * Get the entity type id of the target bundle.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout object to get the target bundle id for.
   *
   * @return string
   */
  public function getTargetBundleEntityType(EntityLayoutInterface $entity_layout) {
    return $this->entityTypeManager
      ->getDefinition($entity_layout->getTargetEntityType())
      ->getBundleEntityType();
  }

  /**
   * Try to find a entity layout block by UUID.
   *
   * @param string $uuid
   *   The entity layout block UUID to search for.
   *
   * @return EntityLayoutBlockInterface|null
   */
  public function getContentBlockByUuid($uuid) {
    $query = $this->entityTypeManager
      ->getStorage('entity_layout_block')
      ->getQuery()
      ->condition('uuid', $uuid)
      ->range(0, 1);

    $results = $query->execute();

    if (!count($results)) {
      return NULL;
    }

    $entity = $this->entityTypeManager
      ->getStorage('entity_layout_block')
      ->load(array_keys($results)[0]);

    return $entity;
  }

  /**
   * Get content blocks for the specified entity layout and content
   * entity combination.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout to filter on.
   * @param ContentEntityInterface $entity
   *   The content entity to filter on.
   *
   * @return EntityLayoutBlockInterface[]
   */
  public function getContentBlocks(EntityLayoutInterface $entity_layout, ContentEntityInterface $entity) {
    return $this->entityTypeManager
      ->getStorage('entity_layout_block')
      ->loadByProperties([
        'entity_id' => $entity->id(),
        'layout' => $entity_layout->id(),
      ]);
  }

  /**
   * Get a list of entity layout blocks for the specified entity.
   *
   * @param ContentEntityInterface $entity
   *   The content entity to get entity layout blocks for.
   *
   * @return EntityLayoutBlockInterface[]
   */
  public function getContentBlocksByEntity(ContentEntityInterface $entity) {
    return $this->entityTypeManager
      ->getStorage('entity_layout_block')
      ->loadByProperties([
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
      ]);
  }

  /**
   * Get all content blocks for the specified content entity as a collection
   * of block instances.
   *
   * @param ContentEntityInterface $entity
   *   The content entity to get blocks for.
   *
   * @return BlockPluginCollection
   */
  public function getContentBlockCollection(ContentEntityInterface $entity) {
    $instances = [];

    $content_blocks = $this->getContentBlocksByEntity($entity);

    foreach ($content_blocks as $content_block) {
      $instances[$content_block->uuid()] = $content_block->getBlock()->getConfiguration();
    }

    return new BlockPluginCollection($this->blockManager, $instances);
  }

  /**
   * Check if the supplied entity layout has any content blocks.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout to check content blocks for.
   *
   * @return bool
   */
  public function hasContentBlocks(EntityLayoutInterface $entity_layout, ContentEntityInterface $entity) {
    return (bool) $this->entityTypeManager
      ->getStorage('entity_layout_block')
      ->getQuery()
      ->condition('entity_id', $entity->id())
      ->condition('layout', $entity_layout->id())
      ->count()
      ->execute();
  }

  /**
   * Transfer the default entity layout blocks to content entities.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout to transfer blocks from.
   * @param ContentEntityInterface $content_entity
   *   The content entity to transfer the block configuration to.
   *
   * @return EntityLayoutBlockInterface[]
   */
  public function transferDefaultBlocks(EntityLayoutInterface $entity_layout, ContentEntityInterface $content_entity) {
    $block_entities = [];

    // Create a entity layout block entity from each default block
    // configuration and create a new UUID.
    foreach ($entity_layout->getBlocks() as $block) {
      $configuration = $block->getConfiguration();

      $block_entity = EntityLayoutBlock::create([
        'layout' => $entity_layout->id(),
        'config' => $configuration,
        'entity_id' => $content_entity,
        'entity_type' => $content_entity->getEntityTypeId(),
      ]);

      $block_entities[] = $block_entity;
    }

    /** @var ContentEntityInterface $block_entity */
    foreach ($block_entities as $block_entity) {
      $block_entity->save();
    }

    return $block_entities;
  }

  /**
   * Transfer the supplied blocks to the specified content entity.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout the entity layout blocks belongs to.
   * @param ContentEntityInterface $content_entity
   *   The content entity to transfer blocks to.
   * @param BlockPluginCollection $block_collection
   *   The blocks to transfer.
   *
   * @return EntityLayoutBlockInterface[]
   */
  public function transferBlocks(EntityLayoutInterface $entity_layout, ContentEntityInterface $content_entity, BlockPluginCollection $block_collection) {
    $block_entities = [];

    // Create a entity layout block entity from each default block
    // configuration and create a new UUID.
    /** @var BlockPluginInterface $block */
    foreach ($block_collection as $block) {
      $configuration = $block->getConfiguration();

      $block_entity = EntityLayoutBlock::create([
        'layout' => $entity_layout->id(),
        'config' => $configuration,
        'entity_id' => $content_entity,
        'entity_type' => $content_entity->getEntityTypeId(),
      ]);

      $block_entities[] = $block_entity;
    }

    /** @var ContentEntityInterface $block_entity */
    foreach ($block_entities as $block_entity) {
      $block_entity->save();
    }

    return $block_entities;
  }

  /**
   * Reset the content layout to the default entity layout settings by
   * removing all content entity blocks created for the content entity.
   *
   * @param ContentEntityInterface $entity
   *   The content entity to remove entity layout blocks from.
   *
   * @return bool
   */
  public function resetContentEntityLayout(ContentEntityInterface $entity) {
    $content_blocks = $this->getContentBlocksByEntity($entity);

    try {
      $this->entityTypeManager
        ->getStorage('entity_layout_block')
        ->delete($content_blocks);
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets a list of all available blocks sorted by category and label.
   *
   * @return array[]
   *
   * @deprecated This will be replaced by the getSystemBlocks() method.
   */
  public function getBlocks() {
    return $this->getSystemBlocks();
  }

  /**
   * Gets a list of all available blocks sorted by category and label.
   *
   * @return array[]
   */
  public function getSystemBlocks() {
    $contexts = $this->contextRepository->getAvailableContexts();
    $blocks = $this->blockManager->getDefinitionsForContexts($contexts);

    return $this->blockManager->getSortedDefinitions($blocks);
  }
}
