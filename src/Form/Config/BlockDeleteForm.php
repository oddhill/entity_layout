<?php

namespace Drupal\entity_layout\Form\Config;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlockDeleteForm extends ConfirmFormBase {

  /**
   * The entity layout if it has been loaded once.
   *
   * @var EntityLayoutInterface
   */
  protected $entityLayout;

  /**
   * The entity layout manager.
   *
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * The entity layout service.
   *
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * Constructs a new BlockDeleteForm.
   *
   * @param EntityLayoutManager $entityLayoutManager
   *   The entity layout manager.
   * @param EntityLayoutService $entityLayoutService
   */
  public function __construct(
    EntityLayoutManager $entityLayoutManager,
    EntityLayoutService $entityLayoutService
  ) {
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_layout_config_block_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $parameters = $this->getRouteMatch()->getParameters()->all();

    $entity_layout = $this->getEntityLayoutFromRouteMatch();
    $block = $entity_layout->getBlock($parameters['block_id']);

    return $this->t('Are you sure you want to remove the @label block.', [
      '@label' => $block->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity_layout = $this->getEntityLayoutFromRouteMatch();

    $target_entity_type = $entity_layout
      ->getTargetEntityType();

    $bundle_entity_type = $this->entityLayoutService
      ->getTargetBundleEntityType($entity_layout);

    return Url::fromRoute("entity_layout.{$target_entity_type}.layout", [
      $bundle_entity_type => $this->entityLayout->getTargetBundle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Remove the cancel button if this is an AJAX request since Drupals built
    // in modal dialogues does not handle buttons that are not a primary
    // button very well.
    if ($this->getRequest()->isXmlHttpRequest()) {
      unset($form['actions']['cancel']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getRouteMatch()->getParameters()->all();
    $entity_layout = $this->getEntityLayoutFromRouteMatch();

    $block = $entity_layout->getBlock($parameters['block_id']);
    $entity_layout->removeBlock($parameters['block_id']);

    drupal_set_message($this->t('The @label block has been removed.', [
      '@label' => $block->label(),
    ]));

    $entity_layout->save();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Get the entity layout object from the route match object.
   *
   * @return EntityLayoutInterface
   */
  protected function getEntityLayoutFromRouteMatch() {
    if (!$this->entityLayout) {
      $this->entityLayout = $this->entityLayoutManager
        ->getFromRouteMatch($this->getRouteMatch());
    }

    return $this->entityLayout;
  }
}
