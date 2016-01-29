<?php

namespace Drupal\entity_layout\Form\Content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutBlockInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlockDeleteForm extends ConfirmFormBase {

  /**
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * The entity layout content block.
   *
   * @var EntityLayoutBlockInterface
   */
  private $entityLayoutBlock;

  /**
   * The content entity.
   *
   * @var ContentEntityInterface
   */
  private $contentEntity;

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
   * {@inheritdoc}
   */
  public function getQuestion() {
    $block = $this->getEntityLayoutBlockFromRouteMatch()->getBlock();

    return $this->t('Are you sure you want to remove the @label block.', [
      '@label' => $block->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $content_entity = $this->getContentEntityFromRouteMatch();
    $entity_type_id = $content_entity->getEntityTypeId();

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $content_entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_layout_content_block_delete_form';
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
    $this->entityLayoutManager->removeContentBlock($this->entityLayoutBlock);

    drupal_set_message($this->t('The @label block has been removed', [
      '@label' => $this->entityLayoutBlock->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Get an entity layout block content entity based on the route match
   * parameters.
   *
   * @return EntityLayoutBlockInterface
   */
  protected function getEntityLayoutBlockFromRouteMatch() {
    if (!$this->entityLayoutBlock) {
      $parameters = $this->getRouteMatch()->getParameters()->all();

      $this->entityLayoutBlock = $this->entityLayoutService
        ->getContentBlockByUuid($parameters['block_id']);
    }

    return $this->entityLayoutBlock;
  }

  /**
   * Attempt to get a content entity from the route match.
   *
   * @return ContentEntityInterface|null
   */
  protected function getContentEntityFromRouteMatch() {
    if (!$this->contentEntity) {
      $parameters = $this->getRouteMatch()->getParameters();
      $entity_type_id = $parameters->get('entity_type_id');

      if ($entity_type_id && $parameters->has($entity_type_id)) {
        $this->contentEntity = $parameters->get($entity_type_id);
      }
    }

    return $this->contentEntity;
  }
}
