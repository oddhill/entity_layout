<?php

namespace Drupal\entity_layout;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

class EntityLayoutService {

  /**
   * The Drupal entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal block manager.
   *
   * @var BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The Drupal context repository.
   *
   * @var ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * EntityLayoutService constructor.
   *
   * @param BlockManagerInterface $blockManager
   * @param ContextRepositoryInterface $contextRepository
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    BlockManagerInterface $blockManager,
    ContextRepositoryInterface $contextRepository,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
  }

  /**
   * Get the label for the target entity.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout object to get the target entity label for.
   *
   * @return string
   */
  public function getTargetEntityLabel(EntityLayoutInterface $entity_layout) {
    return $this->entityTypeManager
      ->getDefinition($entity_layout->getTargetEntityType())
      ->getLabel();
  }

  /**
   * Get the bundle label for the target bundle.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout object to get the target bundle label for.
   *
   * @return null|string
   */
  public function getTargetBundleLabel(EntityLayoutInterface $entity_layout) {
    return $this->entityTypeManager
      ->getDefinition($entity_layout->getTargetEntityType())
      ->getBundleLabel();
  }

  /**
   * Get the entity type id of the target bundle.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout object to get the target bundle id for.
   *
   * @return string
   */
  public function getTargetBundleEntityType(EntityLayoutInterface $entity_layout) {
    return $this->entityTypeManager
      ->getDefinition($entity_layout->getTargetEntityType())
      ->getBundleEntityType();
  }

  /**
   * Gets a list of all available blocks sorted by category and label.
   *
   * @return array[]
   */
  public function getBlocks() {
    $contexts = $this->contextRepository->getAvailableContexts();
    $blocks = $this->blockManager->getDefinitionsForContexts($contexts);
    return $this->blockManager->getSortedDefinitions($blocks);
  }
}
