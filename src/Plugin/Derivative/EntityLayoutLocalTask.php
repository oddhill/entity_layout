<?php

namespace Drupal\entity_layout\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local layout task definitions for all entity bundles.
 */
class EntityLayoutLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an EntityLayoutLocalTask object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    RouteProviderInterface $route_provider,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation
  ) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // If the field ui base route property is not set then we won't know
      // where to attach the manage layout page so skip this entity type.
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }

      // Add a local task for the manage layout page (config).
      $this->derivatives["entity_layout_{$entity_type_id}_config"] = [
        'title' => $this->t('Manage layout'),
        'route_name' => "entity_layout.{$entity_type_id}.layout",
        'base_route' => $route_name,
        'weight' => 4,
      ] + $base_plugin_definition;

      // Add a local task for the layout page (content).
      if ($entity_type->hasLinkTemplate('canonical')) {
        $this->derivatives["entity_layout_{$entity_type_id}_content"] = [
          'title' => $this->t('Layout'),
          'route_name' => "entity_layout.{$entity_type_id}.content.layout",
          'base_route' => "entity.{$entity_type_id}.canonical",
          'weight' => 4,
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }
}
