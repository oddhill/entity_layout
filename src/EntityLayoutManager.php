<?php

namespace Drupal\entity_layout;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_layout\Entity\EntityLayoutBlock;

class EntityLayoutManager {

  /**
   * The Drupal entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EntityLayoutManager constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get all entity layouts.
   *
   * @return EntityLayoutInterface|[]
   */
  public function getAll() {
    return $this->getEntityLayoutStorage()->loadMultiple();
  }

  /**
   * Attempts to load an entity layout for the supplied entity type and bundle.
   *
   * If no existing entity layout is found a new entity layout instance will be
   * created and returned.
   *
   * @param string $entity_type_id
   *   The target entity type.
   * @param $bundle
   *   The target bundle.
   *
   * @return \Drupal\entity_layout\EntityLayoutInterface
   */
  public function getEntityLayout($entity_type_id, $bundle) {
    $entity_layout_id = $entity_type_id . '.' . $bundle;

    $entity_layout = $this->getEntityLayoutStorage()->load($entity_layout_id);

    if ($entity_layout) {
      return $entity_layout;
    }

    return $this->getEntityLayoutStorage()->create([
      'target_entity_type' => $entity_type_id,
      'target_bundle' => $bundle,
    ]);
  }

  /**
   * Attempt to load an entity layout from the route match.
   *
   * @param RouteMatchInterface $route_match
   *   The route match object.
   *
   * @return \Drupal\entity_layout\EntityLayoutInterface|null
   *   The entity layout object if found.
   */
  public function getFromRouteMatch(RouteMatchInterface $route_match) {
    $parameters = $route_match->getParameters()->all();

    if (!isset($parameters['entity_type_id']) || !isset($parameters['bundle'])) {
      return NULL;
    }

    $entity_type_id = $parameters['entity_type_id'];
    $bundle = $parameters['bundle'];

    // If the page being loaded is for a content entity then we need to use
    // the bundle from the content entity instead as the bundle name since
    // the one supplied in the route parameters is the key for where the
    // bundle is stored in the database only.
    if (isset($parameters[$entity_type_id]) && $parameters[$entity_type_id] instanceof ContentEntityInterface) {
      /** @var ContentEntityInterface $content_entity */
      $content_entity = $parameters[$entity_type_id];
      $bundle = $content_entity->bundle();
    }

    return $this->getEntityLayout($entity_type_id, $bundle);
  }

  /**
   * Create a new entity layout block.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout object to create a block for.
   * @param ContentEntityInterface $content_entity
   *   The content entity to associate the block with.
   * @param array $configuration
   *   The block configuration.
   *
   * @return EntityLayoutBlockInterface|static
   */
  public function createContentBlock(EntityLayoutInterface $entity_layout, ContentEntityInterface $content_entity, array $configuration) {
    $entity_layout_block = EntityLayoutBlock::create([
      'layout' => $entity_layout->id(),
      'config' => $configuration,
      'entity_id' => $content_entity,
      'entity_type' => $content_entity->getEntityTypeId(),
    ]);
    return $entity_layout_block;
  }

  /**
   * Remove the supplied block content.
   *
   * @param EntityLayoutBlockInterface $block
   */
  public function removeContentBlock(EntityLayoutBlockInterface $entityLayoutBlock) {
    return $entityLayoutBlock->delete();
  }

  /**
   * Returns the entity layout storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function getEntityLayoutStorage() {
    return $this->entityTypeManager->getStorage('entity_layout');
  }
}
