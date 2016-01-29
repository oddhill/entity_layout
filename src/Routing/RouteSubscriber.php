<?php

namespace Drupal\entity_layout\Routing;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @todo Clean up some of the route generation code.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity layout manager.
   *
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   *
   * @param EntityLayoutManager $entityLayoutManager
   *   The entity layout manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EntityLayoutManager $entityLayoutManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityLayoutManager = $entityLayoutManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $this->addEntityLayoutContentRoutes($collection);
    $this->addEntityLayoutConfigRoutes($collection);
    $this->buildEntityLayoutRoutes($collection);
  }

  /**
   * Add custom routes for each content entity.
   *
   * @param RouteCollection $collection
   */
  protected function addEntityLayoutContentRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // If the field ui base route property is not set then we won't know
      // where to attach the manage layout page so skip this entity type.
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }

      // We can only add layout to entities that have a canonical path.
      if (!$entity_canonical_path = $entity_type->getLinkTemplate('canonical')) {
        continue;
      }

      // Try to get the route for the edit form from the route collection.
      if (!$entity_canonical_route = $collection->get("entity.{$entity_type_id}.canonical")) {
        continue;
      }

      $options = $entity_canonical_route->getOptions();

      if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
        $options['parameters'][$bundle_entity_type] = array(
          'type' => 'entity:' . $bundle_entity_type,
        );
      }

      $options['_entity_layout'] = TRUE;

      $defaults = [
        'entity_type_id' => $entity_type_id,
      ];

      // Inherit admin route status from edit route, if exists.
      $is_admin = FALSE;

      $entity_edit_route = "entity.$entity_type_id.edit_form";

      if ($edit_route = $collection->get($entity_edit_route)) {
        $is_admin = (bool) $edit_route->getOption('_admin_route');
      }

      $options['_admin_route'] = $is_admin;

      // If the entity type has no bundles and it doesn't use {bundle} in its
      // admin path, use the entity type.
      if (strpos($entity_canonical_path, '{bundle}') === FALSE) {
        $defaults['bundle'] = !$entity_type->hasKey('bundle')
          ? $entity_type_id
          : $entity_type->getKey('bundle');
      }

      $requirements = [
        '_permission' => 'create entity layouts',
      ];

      // Entity layout content layout form.
      $collection->add("entity_layout.{$entity_type_id}.content.layout", new Route(
        "{$entity_canonical_path}/layout",
        [
          '_entity_form' => 'entity_layout.content-edit',
          '_title' => 'Layout',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Entity reset layout form.
      $collection->add("entity_layout.{$entity_type_id}.content.reset", new Route(
        "{$entity_canonical_path}/layout/reset",
        [
          '_entity_form' => 'entity_layout.reset-layout',
          '_title' => 'Reset layout',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Entity layout content block library.
      $collection->add("entity_layout.{$entity_type_id}.content.block.library", new Route(
        "{$entity_canonical_path}/layout/block/library",
        [
          '_controller' => '\\Drupal\\entity_layout\\Controller\\EntityLayoutController::contentBlockLibrary',
          '_title' => 'Block library',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Entity layout content block add form.
      $collection->add("entity_layout.{$entity_type_id}.content.block.add", new Route(
        "{$entity_canonical_path}/layout/block/add/{block_id}",
        [
          '_form' => '\\Drupal\\entity_layout\\Form\\ContentBlockAddForm',
          '_title' => 'Add block',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Entity layout content block edit form.
      $collection->add("entity_layout.{$entity_type_id}.content.block.edit", new Route(
        "{$entity_canonical_path}/layout/block/edit/{block_id}",
        [
          '_form' => '\\Drupal\\entity_layout\\Form\\ContentBlockEditForm',
          '_title' => 'Edit block',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Entity layout content block remove form.
      $collection->add("entity_layout.{$entity_type_id}.content.block.remove", new Route(
        "{$entity_canonical_path}/layout/block/remove/{block_id}",
        [
          '_form' => '\\Drupal\\entity_layout\\Form\\ContentBlockDeleteForm',
          '_title' => 'Remove block',
        ] + $defaults,
        $requirements,
        $options
      ));
    }
  }

  /**
   * Add custom routes for each config entity.
   *
   * @param RouteCollection $collection
   */
  protected function addEntityLayoutConfigRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // If the field ui base route property is not set then we won't know
      // where to attach the manage layout page so skip this entity type.
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }

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
        '_permission' => 'administer entity layouts',
      ];

      // Entity layout config entity page.
      $collection->add("entity_layout.$entity_type_id.layout", new Route(
        "{$path}/layout",
        [
          '_entity_form' => 'entity_layout.config-edit',
          '_title' => 'Manage layout',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Config block library route.
      $collection->add("entity_layout.$entity_type_id.block.library", new Route(
        "{$path}/layout/block/library",
        [
          '_controller' => '\\Drupal\\entity_layout\\Controller\\EntityLayoutController::configBlockLibrary',
          '_title' => 'Block library',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Config block add route.
      $collection->add("entity_layout.$entity_type_id.block.add", new Route(
        "{$path}/layout/block/add/{block_id}",
        [
          '_form' => '\\Drupal\\entity_layout\\Form\\ConfigBlockAddForm',
          '_title' => 'Add block',
        ] + $defaults,
        $requirements,
        $options
      ));

      // Config block edit route.
      $collection->add("entity_layout.$entity_type_id.block.edit", new Route(
        "{$path}/layout/block/edit/{block_id}",
        [
          '_form' => '\\Drupal\\entity_layout\\Form\\ConfigBlockEditForm',
          '_title' => 'Edit block',
        ] + $defaults,
        $requirements,
        $options
      ));
    }
  }

  /**
   * Override the routes for each content entity that has been configured by
   * entity layout.
   *
   * @param RouteCollection $collection
   */
  protected function buildEntityLayoutRoutes(RouteCollection $collection) {

    $requirements = [
      '_entity_access' => 'entity_layout.view',
    ];

    $parameters = [
      'entity_layout' => [
        'type' => 'entity:entity_layout',
      ],
    ];

    /* @var $entity_layout EntityLayoutInterface */
    foreach ($this->entityLayoutManager->getAll() as $entity_layout) {
      $target_entity_id = $entity_layout->getTargetEntityType();

      $entities = $this->loadEntities($target_entity_id, $entity_layout->getTargetBundle());

      $entity_parameters = [
        $target_entity_id => [
          'type' => "entity:{$target_entity_id}",
        ],
      ];

      foreach ($entities as $entity) {
        $entity_path = $entity->toUrl('canonical')->getInternalPath();
        $route_name = "entity_layout.{$entity->getEntityTypeId()}.{$entity->id()}";

        $defaults = [
          '_entity_view' => 'entity_layout',
          '_title' => $entity->label(),
          'entity_layout' => $entity_layout->id(),
          'entity_type_id' => $entity->getEntityTypeId(),
          $target_entity_id => $entity->id(),
        ];

        $options = [
          '_entity_layout' => TRUE,
          'parameters' => $parameters + $entity_parameters,
        ];

        $route = new Route($entity_path, $defaults, $requirements, $options);

        $collection->add($route_name, $route);
      }
    }
  }

  /**
   * Load entities based on entity and bundle type.
   *
   * @param string $entity_type
   *   The entity type to load entities for.
   * @param string $bundle_type
   *   The bundle type the load entities for.
   *
   * @return ContentEntityInterface[]
   */
  protected function loadEntities($entity_type, $bundle_type) {
    $definition = $this->entityTypeManager->getDefinition($entity_type);

    $properties = [];

    // Get the key used for the bundle on this entity and set it to the query
    // properties if it exists.
    if ($bundle_key = $definition->getKey('bundle')) {
      $properties[$bundle_key] = $bundle_type;
    }

    return $this->entityTypeManager
      ->getStorage($entity_type)
      ->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -210];
    return $events;
  }
}
