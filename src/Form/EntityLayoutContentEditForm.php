<?php

namespace Drupal\entity_layout\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class EntityLayoutContentEditForm extends EntityLayoutFormBase
{
  /**
   *
   */
  protected $initialized = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function getEntityLayout($entity_type_id, $bundle) {
    return $this->entityLayoutManager->getEntityLayout($entity_type_id, $bundle);
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo Remove this when tab logic has been implemented.
    if ($this->entity->isNew()) {
      drupal_set_message($this->t('A default layout must be configured for this content to be able to edit the layout.'), 'warning');
      return $form;
    }

    if (!$this->entityLayoutService->hasContentBlocks($this->entity, $this->contentEntity)) {
      $this->initialized = FALSE;

      drupal_set_message($this->t('This layout must be initialized before you can customize it.'), 'warning');

      $form['actions'] = $this->actions($form, $form_state);

      return $form;
    }

    $this->initialized = TRUE;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $target_entity_type = $this->entity->getTargetEntityType();
    $target_bundle = $this->entity->getTargetBundle();
    $bundle_entity_type = $this->entityLayoutService->getTargetBundleEntityType($this->entity);

    $form['add_block_button'] = [
      '#type' => 'link',
      '#title' => $this->t('Place block'),
      '#url' => Url::fromRoute("entity_layout.{$target_entity_type}.content.block.library", [
        $target_entity_type => $this->contentEntity->id(),
      ]),
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];

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

    $blocks = $this->entityLayoutService
      ->getContentBlockCollection($this->contentEntity)
      ->getSortedByWeight();

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($blocks) / 2);

    foreach ($blocks as $block_id => $block) {
      $form['layout'][$block_id] = $this->buildBlockRow($block, $weight_delta, $target_entity_type, $bundle_entity_type, $target_bundle);
    }

    return $form;
  }

  public function initializeLayout(array $form, FormStateInterface $form_state) {
    $this->entityLayoutService->transferDefaultBlocks($this->entity, $this->contentEntity);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->contentEntity->getEntityTypeId();

    if (!$this->initialized) {
      return [
        'initialize' => [
          '#type' => 'submit',
          '#value' => $this->t('Initialize'),
          '#submit' => ['::initializeLayout'],
        ],
      ];
    }

    $actions = parent::actions($form, $form_state);

    $actions['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset layout'),
      '#url' => Url::fromRoute("entity_layout.{$entity_type_id}.content.reset", [
        $entity_type_id => $this->contentEntity->id(),
      ]),
      '#attributes' => [
        'class' => ['button', 'use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    $layout_blocks = $form_state->getValue('layout', []);

    if (!is_array($layout_blocks)) {
      $layout_blocks = [];
    }

    // If no content blocks has been created for this entity then we need to
    // transfer the default blocks to the loaded content entity as entity
    // layout blocks.
    if (!$this->entityLayoutService->hasContentBlocks($this->entity, $this->contentEntity)) {
      $block_collection = $this->entity->getDefaultBlocks();

      // Update the default blocks with new configuration values from the
      // form state before transferring the blocks since weights may have
      // been changed.
      foreach ($layout_blocks as $layout_block_id => $layout_block_configuration) {
        $configuration = $block_collection->get($layout_block_id)->getConfiguration();
        $block_collection->setInstanceConfiguration($layout_block_id, $layout_block_configuration + $configuration);
      }

      $this->entityLayoutService->transferBlocks($this->entity, $this->contentEntity, $block_collection);
    }
    else {
      $content_blocks = $this->entityLayoutService->getContentBlocksByEntity($this->contentEntity);

      foreach ($layout_blocks as $layout_block_id => $layout_block_configuration) {
        foreach ($content_blocks as $content_block) {
          if ($layout_block_id !== $content_block->uuid()) {
            continue;
          }

          $content_block->updateBlock($layout_block_configuration);
          $content_block->save();
        }
      }
    }

    drupal_set_message($this->t('The layout for @label has been saved.', [
      '@label' => $this->contentEntity->label(),
    ]));
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
          'url' => Url::fromRoute("entity_layout.{$target_entity_type}.content.block.edit", [
            'block_id' => $configuration['uuid'],
            $target_entity_type => $this->contentEntity->id(),
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
          'url' => Url::fromRoute("entity_layout.{$target_entity_type}.content.block.remove", [
            'block_id' => $configuration['uuid'],
            $target_entity_type => $this->contentEntity->id(),
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
