<?php

namespace Drupal\entity_layout\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class EntityLayoutRouteEnhancer implements RouteEnhancerInterface {

  /**
   * The Drupal entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityLayoutRouteEnhancer constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return ($route->hasOption('_entity_layout'));
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {

    // Entity layout forms only need the actual name of the bundle they're dealing
    // with, not an upcasted entity object, so provide a simple way for them
    // to get it.
    $bundle = $this->entityTypeManager
      ->getDefinition($defaults['entity_type_id'])
      ->getBundleEntityType();

    if ($bundle && isset($defaults[$bundle])) {
      $defaults['bundle'] = $defaults['_raw_variables']->get($bundle);
    }

    return $defaults;
  }
}
