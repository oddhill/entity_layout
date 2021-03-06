<?php

namespace Drupal\entity_layout\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class EntityLayoutConfigEditForm extends EntityLayoutFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLayout($entity_type_id, $bundle) {
    return $this->entityLayoutManager->getEntityLayout($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($this->entity->isNew()) {
      drupal_set_message($this->t('A layout has not yet been configured for this entity. To enable the entity layout for this entity you must configure the settings and default blocks for this entity layout.'), 'warning');
    }

    $target_entity_type = $this->entity->getTargetEntityType();
    $target_bundle = $this->entity->getTargetBundle();
    $bundle_entity_type = $this->entityLayoutService->getTargetBundleEntityType($this->entity);

    $form['text'] = [
      '#markup' => '<p>' . $this->t('This page will allow you to manage the default layout for when a @bundle is displayed. This may also be configured at the content entity level to override specific content entities.', [
          '@bundle' => $this->entityLayoutService->getTargetBundleLabel($this->entity),
        ]) . '</p>',
    ];

    $form['add_block_button'] = array(
      '#type' => 'link',
      '#title' => $this->t('Place block'),
      '#url' => Url::fromRoute("entity_layout.{$target_entity_type}.block.library", [
        $bundle_entity_type => $target_bundle,
      ]),
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    );

    $form['layout'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Category'),
        $this->t('Weight'),
        $this->t('Actions'),
      ],
      '#empty' => $this->t('No block have been placed for this entity layout.'),
      '#attributes' => [
        'class' => ['entity-layout-overview'],
        'id' => 'entity-layout-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'block-weight',
        ],
      ],
    ];

    $blocks = $this->entity->getDefaultBlocks();

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($blocks) / 2);

    // Block rows.
    foreach ($blocks as $block_id => $block) {
      $form['layout'][$block_id] = $this->buildBlockRow($block, $weight_delta, $target_entity_type, $bundle_entity_type, $target_bundle);
    }

    $form['allowed_blocks'] = array(
      '#type' => 'details',
      '#title' => $this->t('Allowed blocks'),
      '#tree' => TRUE,
    );

    foreach ($this->entityLayoutService->getSystemBlocks() as $block_id => $block) {
      $form['allowed_blocks'][$block_id] = [
        '#type' => 'checkbox',
        '#title' => $block['admin_label'],
        '#default_value' => $this->entity->blockIsAllowed($block_id),
      ];
    }

    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Save'),
      ],
    );

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Only save the blocks that are actually allowed and not every key value
    // pair from the form.
    $allowed_blocks = array_filter($form_state->getValue('allowed_blocks', []));
    $this->entity->setAllowedBlocks($allowed_blocks);

    // Update the block configuration.
    $layout = $form_state->getValue('layout', []);

    if (is_array($layout)) {
      foreach ($layout as $block_id => $block_configuration) {
        $this->entity->updateBlock($block_id, $block_configuration);
      }
    }

    $this->save($form, $form_state);

    drupal_set_message($this->t('The entity layout has been saved.'));
  }

  /**
   * Build a block row for the layout table.
   *
   * @param BlockPluginInterface $block
   *   The block to render a row for.
   * @param $weight_delta
   *   The calculated weight delta.
   * @param string $target_entity_type
   * @param string $bundle_entity_type
   * @param string $target_bundle
   *
   * @return array
   */
  protected function buildBlockRow(BlockPluginInterface $block, $weight_delta, $target_entity_type, $bundle_entity_type, $target_bundle) {
    $configuration = $block->getConfiguration();

    $block_row = [
      '#attributes' => [
        'class' => ['draggable']
      ],
    ];

    $block_row['label'] = [
      '#plain_text' => $block->label(),
    ];

    $block_row['category'] = [
      '#plain_text' => $block->getPluginDefinition()['category'],
    ];

    $block_row['weight'] = [
      '#type' => 'weight',
      '#default_value' => isset($configuration['weight']) ? $configuration['weight'] : 0,
      '#delta' => $weight_delta,
      '#title' => $this->t('Weight for @block block', ['@block' => $block->label()]),
      '#title_display' => 'invisible',
      '#attributes' => [
        'class' => ['block-weight'],
      ],
    ];

    $block_row['operations'] = [
      '#type' => 'operations',
      '#links' => [
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute("entity_layout.{$target_entity_type}.block.edit", [
            'block_id' => $configuration['uuid'],
            $bundle_entity_type => $target_bundle,
          ]),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 700,
            ]),
          ],
        ],
        'remove' => [
          'title' => $this->t('Remove'),
          'url' => Url::fromRoute("entity_layout.{$target_entity_type}.block.remove", [
            'block_id' => $configuration['uuid'],
            $bundle_entity_type => $target_bundle,
          ]),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 700,
            ]),
          ],
        ],
      ],
    ];

    return $block_row;
  }
}
