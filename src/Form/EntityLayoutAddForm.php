<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityLayoutAddForm extends EntityForm
{
  /**
   * The entity layout service class.
   *
   * @var EntityLayoutService
   */
  private $entityLayoutService;

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
      '#options' => $entities,
      '#description' => $this->t('Select entity type.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::entitySelect',
        'wrapper' => 'entity-layout-bundle-select',
      ],
    ];

    $selected_entity = $form_state->hasValue('entity')
      ? $form_state->getValue('entity')
      : key($entities);

    $bundles = $this->entityLayoutService
      ->getEntityTypeBundlesList($selected_entity);

    $form['bundle'] = [
      '#prefix' => '<div id="entity-layout-bundle-select">',
      '#suffix' => '</div>',
      '#type' => 'select',
      '#options' => $bundles,
      '#description' => $this->t('Select bundle type.'),
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return int
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity');
    $bundle_type = $form_state->getValue('bundle', $entity_type);

    var_dump($entity_type);
    var_dump($bundle_type);
    var_dump($this->entity);
    die;
  }


  /**
   * Ajax callback for the entity select list.
   *
   * @param array $form
   *
   * @return array
   */
  public function entitySelect(array $form) {
    return $form['bundle'];
  }
}
