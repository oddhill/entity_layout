<?php

namespace Drupal\entity_layout\Routing;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
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
    $this->addEntityLayoutRoutes($collection);
    //$this->overrideEntityViewRoutes($collection);
  }

  /**
   * Add custom routes for each content entity needed by the entity
   * layout module.
   *
   * @param RouteCollection $collection
   */
  protected function addEntityLayoutRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {

        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }

        $path = $entity_route->getPath();
        $options = $entity_route->getOptions();

        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
          $options['parameters'][$bundle_entity_type] = array(
            'type' => 'entity:' . $bundle_entity_type,
          );
        }

        $options['_entity_layout'] = TRUE;

        $defaults = [
          'entity_type_id' => $entity_type_id,
        ];

        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (strpos($path, '{bundle}') === FALSE) {
          $defaults['bundle'] = !$entity_type->hasKey('bundle')
            ? $entity_type_id
            : $entity_type->getKey('bundle');
        }

        $requirements = [
          '_permission' => 'access content',
        ];

        $collection->add("entity_layout.$entity_type_id.layout", new Route(
          "{$path}/layout",
          [
            '_entity_form' => 'entity_layout.edit',
            '_title' => 'Manage layout',
          ] + $defaults,
          $requirements,
          $options
        ));

        $collection->add("entity_layout.$entity_type_id.block_library", new Route(
          "{$path}/layout/block/library",
          [
            '_controller' => '\\Drupal\\entity_layout\\Controller\\EntityLayoutController::blockLibrary',
            '_title' => 'Block library',
          ] + $defaults,
          $requirements,
          $options
        ));

        $collection->add("entity_layout.$entity_type_id.block_add", new Route(
          "{$path}/layout/block/add/{block_id}",
          [
            '_form' => '\\Drupal\\entity_layout\\Form\\BlockAddForm',
            '_title' => 'Add block',
          ] + $defaults,
          $requirements,
          $options
        ));
      }
    }
  }

  /**
   * Override the routes for each content entity that has been configured by
   * entity layout.
   *
   * @param RouteCollection $collection
   */
  protected function overrideEntityViewRoutes(RouteCollection $collection) {

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      var_dump($entity_type->getGroup());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }
}
