<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntityLayoutFormBase extends EntityForm {

  /**
   * The current content entity object if one has been loaded.
   *
   * @var ContentEntityInterface
   */
  protected $contentEntity;

  /**
   * @var EntityLayoutInterface
   */
  protected $entity;

  /**
   * The entity layout manager class.
   *
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * The entity layout service class.
   *
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * EntityLayoutFormBase constructor.
   *
   * @param EntityLayoutManager $entityLayoutManager
   *   The entity layout manager class.
   * @param EntityLayoutService $entityLayoutService
   *   The entity layout service class.
   */
  public function __construct(EntityLayoutManager $entityLayoutManager, EntityLayoutService $entityLayoutService) {
    $this->entityLayoutManager = $entityLayoutManager;
    $this->entityLayoutService = $entityLayoutService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_layout.manager'),
      $container->get('entity_layout.service')
    );
  }

  /**
   * Get an entity layout object to be used by this form.
   *
   * @param string $entity_type_id
   *   The id of the target entity type.
   * @param string $bundle
   *   The target bundle of the entity type.
   *
   * @return EntityLayoutInterface
   */
  abstract protected function getEntityLayout($entity_type_id, $bundle);

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $parameters = $route_match->getParameters()->all();

    $entity_type_id = $parameters['entity_type_id'];

    // Attempt to load the content entity.
    if (isset($parameters[$entity_type_id]) && $parameters[$entity_type_id] instanceof ContentEntityInterface) {
      $this->contentEntity = $parameters[$entity_type_id];
    }

    return $this->entityLayoutManager->getFromRouteMatch($route_match);
  }
}
