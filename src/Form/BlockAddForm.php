<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Block\BlockPluginInterface;

class BlockAddForm extends BlockFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'entity_layout_block_add_form';
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
   * @return mixed
   */
  protected function persistBlock(BlockPluginInterface $block) {
    // TODO: Implement persistBlock() method.
  }
}
