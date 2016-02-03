<?php

/**
 * @file
 * Contains \Drupal\entity_layout\EntityLayoutListBuilder
 */

namespace Drupal\entity_layout;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for the Entity Layout entity type.
 *
 * @see \Drupal\entity_layout\Entity\EntityLayout
 */
class EntityLayoutListBuilder extends EntityListBuilder
{
  /**
   * The entity layout service class.
   *
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param EntityLayoutService $entityLayoutService
   *   The entity layout service class.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    EntityLayoutService $entityLayoutService
  ) {
    parent::__construct($entity_type, $storage);

    $this->entityLayoutService = $entityLayoutService;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_layout.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'label' => $entity->label(),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * Get the operations for the entity row.
   *
   * @param EntityInterface $entity
   *
   * @return array
   */
  public function getOperations(EntityInterface $entity) {
    /** @var EntityLayoutInterface $entity */

    $bundle_entity_type = $this->entityLayoutService
      ->getTargetBundleEntityType($entity);

    $target_entity_type = $entity->getTargetEntityType();

    $operations = [];

    if ($entity->access('update')) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => Url::fromRoute("entity_layout.{$target_entity_type}.layout", [
          $bundle_entity_type => $entity->getTargetBundle(),
        ]),
      );
    }

    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->toUrl('delete-form'),
      );
    }

    return $operations;
  }


}
