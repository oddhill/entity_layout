<?php

namespace Drupal\entity_layout\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {

        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }

        $path = $entity_route->getPath();
        $options = $this->getRouteOptions($entity_route, $entity_type);
        $defaults = $this->getDefaults($path, $entity_type, $entity_type_id);

        $this->addLayoutTabRoute($collection, $path, $options, $defaults, $entity_type_id);
      }
    }
  }

  /**
   * Get default values for the supplied entity type to use with the route.
   *
   * @param  string $path
   * @param  Object $entity_type
   * @param  Object $entity_type_id
   * @return array
   */
  protected function getDefaults($path, $entity_type, $entity_type_id) {
    $defaults = [
      'entity_type_id' => $entity_type_id,
    ];

    // If the entity type has no bundles and it doesn't use {bundle} in its
    // admin path, use the entity type.
    if (strpos($path, '{bundle}') === FALSE) {
      $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
    }

    return $defaults;
  }

  /**
   * Get options for the supplied entity route and entity type.
   *
   * @param  Object $entity_route
   * @param  Object $entity_type
   * @return array
   */
  protected function getRouteOptions($entity_route, $entity_type) {
    $options = $entity_route->getOptions();

    if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
      $options['parameters'][$bundle_entity_type] = array(
        'type' => 'entity:' . $bundle_entity_type,
      );
    }

    $options['_entity_layout'] = TRUE;

    return $options;
  }

  /**
   * Add route for the entity layout tabs.
   *
   * @param RouteCollection $collection
   * @param string $path
   * @param array  $options
   * @param array  $defaults
   * @param string $entity_type_id
   */
  protected function addLayoutTabRoute(RouteCollection $collection, $path, array $options, array $defaults, $entity_type_id) {
    $defaults = [
      '_entity_form' => 'entity_layout.edit',
      '_title' => 'Manage layout',
    ] + $defaults;

    $requirements = [
      '_entity_access' => 'entity_layout.update',
    ];

    $route = new Route("{$path}/layout", $defaults, $requirements, $options);

    $collection->add("entity.$entity_type_id.display", $route);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }
}
