<?php

namespace Drupal\entity_layout\Form\Content;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;

class BlockAddForm extends BlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_layout_content_block_add_form';
  }

  /**
   * {@inheritdoc}
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
    return $this->blockManager->createInstance($block_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function persistBlock(BlockPluginInterface $block) {
    $content_entity = $this->getContentEntityFromRouteMath();

    $content_block = $this->entityLayoutManager
      ->createContentBlock($this->entityLayout, $content_entity, $block->getConfiguration());

    return $content_block->save();
  }
}
