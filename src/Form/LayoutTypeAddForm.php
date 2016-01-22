<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Form\FormStateInterface;

class LayoutTypwAddForm extends LayoutTypeFormBase {

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select entity type'),
      '#description' => $this->t('Select the entity type you want to create a default layout for.'),
      '#options' => $this->entityLayoutService->getContentEntityLabels(),
      '#required' => TRUE,
    ];

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    $entityType = $form_state->getValue('entity_type');

    return parent::save($form, $form_state);
  }


}
