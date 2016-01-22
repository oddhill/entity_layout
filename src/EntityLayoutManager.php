<?php

namespace Drupal\entity_layout;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

class EntityLayoutManager {

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * EntityLayoutManager constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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

    return $this->getEntityLayout($parameters['entity_type_id'], $parameters['bundle']);
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
