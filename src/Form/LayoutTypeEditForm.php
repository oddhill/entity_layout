<?php

namespace Drupal\entity_layout\Form;

class LayoutTypeEditForm extends LayoutTypeFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getTableHeader() {
    return array(
      $this->t('Block'),
      $this->t('Category'),
      $this->t('Weight'),
      $this->t('Actions'),
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @param ConfigEntityInterface $entity
   *
   * @return array The form structure.
   * The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $entity = NULL) {
    $this->entity = $entity;

    $bundle_name = $entity->getEntityType()->getBundleOf();

    $settings = $entity->getThirdPartySettings('entity_layout');

    $form['#tree'] = TRUE;

    $form['add_block_button'] = array(
      '#type' => 'link',
      '#title' => $this->t('Place block'),
      '#url' => Url::fromRoute("entity_layout.{$bundle_name}.block_library", [
        $entity->getEntityTypeId() => $entity->id(),
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
      '#header' => $this->getTableHeader(),
      '#attributes' => [
        'class' => ['field-ui-overview'],
        'id' => 'field-display-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'block-weight',
        ],
      ],
    ];

    $blocks = new BlockCollection($this->blockManager, $entity->getThirdPartySetting('entity_layout', 'blocks', []));

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = round(count($blocks) / 2);

    // Block rows.
    foreach ($blocks as $block_id => $block) {
      $form['layout'][$block_id] = $this->buildBlockRow($block, $weight_delta);
    }

    $form['settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#tree' => TRUE,
    );

    $form['settings']['allow_custom_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow all custom blocks'),
      '#description' => $this->t('Checking this will allow any custom block content to be placed regardless of if the block has been allowed or not.'),
    ];

    $form['allowed_blocks'] = array(
      '#type' => 'details',
      '#title' => $this->t('Allowed blocks'),
      '#tree' => TRUE,
    );

    foreach ($this->getBlocks() as $block_id => $block) {
      $form['allowed_blocks'][$block_id] = [
        '#type' => 'checkbox',
        '#title' => $block['admin_label'],
        '#default_value' => isset($settings['allowed_blocks'][$block_id]) ? TRUE : FALSE,
      ];
    }

    $form['actions'] = array(
      '#type' => 'actions'
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save'),
    );

    $form['#attached']['library'][] = 'core/drupal.tableheader';

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
    $allowed_blocks = array_filter($form_state->getValue('allowed_blocks', []));

    $this->entity->setThirdPartySetting('entity_layout', 'allowed_blocks', $allowed_blocks);

    $this->entity->save();
  }

  /**
   * Get a list of all available blocks.
   *
   * @return array[]
   */
  protected function getBlocks() {
    $contexts = $this->contextRepository->getAvailableContexts();
    $blocks = $this->blockManager->getDefinitionsForContexts($contexts);

    return $this->blockManager->getSortedDefinitions($blocks);
  }

  protected function buildBlockRow(BlockPluginInterface $block, $weight_delta) {

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
      '#default_value' => 0,
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
          'title' => $this->t('Edit')
        ],
      ],
    ];

    return $block_row;
  }
}
