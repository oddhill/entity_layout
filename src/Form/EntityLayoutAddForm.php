<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityLayoutAddForm extends EntityForm
{
  /**
   * The entity layout.
   *
   * @var EntityLayoutInterface
   */
  protected $entity;

  /**
   * The entity layout service class.
   *
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * EntityLayoutAddForm constructor.
   *
   * @param EntityLayoutService $entityLayoutService
   *   The entity layout service class.
   */
  public function __construct(EntityLayoutService $entityLayoutService) {
    $this->entityLayoutService = $entityLayoutService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_layout.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entities = $this->entityLayoutService->getSupportedEntityTypesList();

    $form['entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Target entity'),
      '#options' => $entities,
      '#description' => $this->t('Select entity type.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::entitySelect',
        'wrapper' => 'entity-layout-bundle-select',
      ],
    ];

    $bundles = [];

    if ($form_state->hasValue('entity')) {
      $bundles = $this->entityLayoutService
        ->getEntityTypeBundlesList($form_state->getValue('entity'));
    }

    $form['bundle'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'entity-layout-bundle-select',
      ],
    ];

    if (count($bundles)) {
      $form['bundle']['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Target bundle'),
        '#options' => $bundles,
        '#description' => $this->t('Select bundle type.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $target_entity = $form_state->getValue('entity');
    $target_bundle = $form_state->getValue('bundle', $target_entity);

    $this->entity->set('target_entity_type', $target_entity);
    $this->entity->set('target_bundle', $target_bundle);

    if ($target_entity !== $target_bundle) {
      drupal_set_message($this->t('A entity layout for the @entity entity and @bundle bundle has been created.', [
        '@entity' => $this->entityLayoutService->getTargetEntityLabel($this->entity),
        '@bundle' => $this->entityLayoutService->getTargetBundleLabel($this->entity),
      ]));
    }
    else {
      drupal_set_message($this->t('A entity layout for the @entity entity has been created.', [
        '@entity' => $this->entityLayoutService->getTargetEntityLabel($this->entity),
      ]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Ajax callback for the entity select list.
   *
   * @param array $form
   *
   * @return array
   */
  public function entitySelect(array $form, FormStateInterface $form_state) {;
    return $form['bundle'];
  }
}
