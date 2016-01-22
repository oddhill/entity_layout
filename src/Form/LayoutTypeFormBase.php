<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\entity_layout\EntityLayoutService;
use Drupal\entity_layout\LayoutInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class LayoutTypeFormBase extends EntityForm {

  /**
   * The layout entity.
   *
   * @var LayoutInterface
   */
  protected $entity;

  /**
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * LayoutFormBase constructor.
   *
   * @param EntityLayoutService $entityLayoutService
   */
  public function __construct(EntityLayoutService $entityLayoutService) {
    $this->entityLayoutService = $entityLayoutService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_layout.service')
    );
  }
}
