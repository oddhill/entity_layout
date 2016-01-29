<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;

class ContentBlockEditForm extends BlockFormBase {

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
    $entity_type_id = $this->contentEntity->getEntityTypeId();

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $this->contentEntity->id(),
    ]);
  }

  /**
   * Prepares the block plugin based on the block ID.
   *
   * @param string $block_id
   *   Either a block ID, or the plugin ID used to create a new block.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The block plugin.
   */
  protected function prepareBlock($block_id) {
    // Make sure blocks have been transferred to content blocks before editing.
    if (!$this->entityLayoutService->hasContentBlocks($this->entityLayout, $this->contentEntity)) {
      $block_collection = $this->entityLayout->getDefaultBlocks();

      // Get the block instance being edited.
      $block = $block_collection->get($block_id);

      // Now remove it from the collection.
      $block_collection->removeInstanceId($block_id);

      // Now transfer the remaining blocks.
      $this->entityLayoutService->transferBlocks($this->entityLayout, $this->contentEntity, $block_collection);

      // Create a content block from the block we removed from the collection and save it.
      $content_block = $this->entityLayoutManager
        ->createContentBlock($this->entityLayout, $this->contentEntity, $block->getConfiguration());

      $content_block->save();

      return $content_block->getBlock();
    }

    return $this->entityLayoutService
      ->getContentBlockByUuid($block_id)
      ->getBlock();
  }

  /**
   * Function to handle persisting of the block once saved.
   *
   * @param  BlockPluginInterface $block
   *   The block to be persisted.
   *
   * @return string
   */
  protected function persistBlock(BlockPluginInterface $block) {
    $configuration = $block->getConfiguration();
    $entity = $this->entityLayoutService->getContentBlockByUuid($configuration['uuid']);

    $entity->updateBlock($configuration);
    $entity->save();
  }
}
