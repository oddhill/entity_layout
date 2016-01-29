<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;

class ContentBlockAddForm extends BlockFormBase {

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
    $entity_type_id = $this->contentEntity->getEntityTypeId();

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $this->contentEntity->id(),
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

    // Make sure default blocks has been transferred to content blocks
    // before persisting.
    if (!$this->entityLayoutService->hasContentBlocks($this->entityLayout, $this->contentEntity)) {
      $this->entityLayoutService->transferDefaultBlocks($this->entityLayout, $this->contentEntity);
    }

    $content_block = $this->entityLayoutManager->createContentBlock(
      $this->entityLayout,
      $this->contentEntity,
      $block->getConfiguration()
    );

    return $content_block->save();
  }
}
