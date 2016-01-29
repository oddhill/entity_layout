<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BlockDeleteFormBase extends ConfirmFormBase {

  /**
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * BlockDeleteFormBase constructor.
   * @param EntityLayoutManager $entityLayoutManager
   * @param EntityLayoutService $entityLayoutService
   */
  public function __construct(EntityLayoutManager $entityLayoutManager, EntityLayoutService $entityLayoutService)
  {
    $this->entityLayoutManager = $entityLayoutManager;
    $this->entityLayoutService = $entityLayoutService;
  }

  /**
   * @param ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_layout.manager'),
      $container->get('entity_layout.service')
    );
  }

  /**
   * The loaded block instance.
   *
   * @var BlockPluginInterface
   */
  protected $block;

  /**
   * The entity layout.
   *
   * @var EntityLayoutInterface
   */
  protected $entityLayout;

  /**
   * Prepare the block to be removed.
   *
   * @return BlockPluginInterface
   */
  abstract protected function prepareBlockInstance();

  /**
   * Prepare the entity layout.
   *
   * @return mixed
   */
  abstract protected function prepareEntityLayout();

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entityLayout = $this->prepareEntityLayout();
    $this->block = $this->prepareBlockInstance();
    return parent::buildForm($form, $form_state);
  }
}
