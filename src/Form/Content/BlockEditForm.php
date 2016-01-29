<?php

namespace Drupal\entity_layout\Form\Content;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;

class BlockEditForm extends BlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_layout_content_block_edit_form';
  }

  /**
   * Get the URL object for the redirect.
   *
   * @return Url
   */
  protected function getRedirectUrl() {
    $content_entity = $this->getContentEntityFromRouteMath();
    $entity_type_id = $content_entity->getEntityTypeId();

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $content_entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($block_id) {
    return $this->entityLayoutService
      ->getContentBlockByUuid($block_id)
      ->getBlock();
  }

  /**
   * {@inheritdoc}
   */
  protected function persistBlock(BlockPluginInterface $block) {
    $configuration = $block->getConfiguration();
    $entity = $this->entityLayoutService->getContentBlockByUuid($configuration['uuid']);

    $entity->updateBlock($configuration);
    $entity->save();
  }
}
