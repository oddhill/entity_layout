<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutBlockInterface;

class ContentBlockDeleteForm extends BlockDeleteFormBase {

  /**
   * The entity layout content block.
   *
   * @var EntityLayoutBlockInterface
   */
  protected $entityLayoutBlock;

  /**
   * The content entity.
   *
   * @var ContentEntityInterface
   */
  protected $contentEntity;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the @label block.', [
      '@label' => $this->block->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity_type_id = $this->contentEntity->getEntityTypeId();
    $entity_id = $this->contentEntity->id();

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $entity_id,
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
  protected function prepareBlockInstance() {
    $parameters = $this->getRouteMatch()->getParameters()->all();
    /** @var ContentEntityInterface $content_entity */
    $content_entity = $parameters[$parameters['entity_type_id']];

    $this->contentEntity = $content_entity;

    // Make sure blocks have been transferred.
    if (!$this->entityLayoutService->hasContentBlocks($this->entityLayout, $content_entity)) {
      $block_collection = $this->entityLayout->getDefaultBlocks();

      // Get the block instance being edited.
      $block = $block_collection->get($parameters['block_id']);

      // Now remove it from the collection.
      $block_collection->removeInstanceId($parameters['block_id']);

      // Now transfer the remaining blocks.
      $this->entityLayoutService->transferBlocks($this->entityLayout, $content_entity, $block_collection);

      // Create a content block from the block we removed from the collection and save it.
      $content_block = $this->entityLayoutManager
        ->createContentBlock($this->entityLayout, $content_entity, $block->getConfiguration());

      $content_block->save();

      return $content_block;
    }

    $this->entityLayoutBlock = $this->entityLayoutService
      ->getContentBlockByUuid($parameters['block_id']);

    return $this->entityLayoutBlock->getBlock();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntityLayout() {
    $parameters = $this->getRouteMatch()->getParameters()->all();
    /** @var ContentEntityInterface $content_entity */
    $content_entity = $parameters[$parameters['entity_type_id']];

    return $this->entityLayoutManager
      ->getEntityLayout($content_entity->getEntityTypeId(), $content_entity->bundle());
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
}
