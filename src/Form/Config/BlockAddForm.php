<?php

namespace Drupal\entity_layout\Form\Config;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;

class BlockAddForm extends BlockFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'entity_layout_config_block_add_form';
  }

  /**
   * Get the URL object for the redirect.
   *
   * @return Url
   */
  protected function getRedirectUrl() {
    $target_entity_type = $this->entityLayout
      ->getTargetEntityType();

    $bundle_entity_type = $this->entityLayoutService
      ->getTargetBundleEntityType($this->entityLayout);

    return Url::fromRoute("entity_layout.{$target_entity_type}.layout", [
      $bundle_entity_type => $this->entityLayout->getTargetBundle(),
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
    return $this->blockManager->createInstance($block_id);
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
    $this->entityLayout->addBlock($block->getConfiguration());
    return $this->entityLayout->save();
  }
}
