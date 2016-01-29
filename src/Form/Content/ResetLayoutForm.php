<?php

namespace Drupal\entity_layout\Form\Content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ResetLayoutForm extends ConfirmFormBase
{
  /**
   * The content entity.
   *
   * @var ContentEntityInterface
   */
  private $contentEntity;

  /**
   * The entity layout.
   *
   * @var EntityLayoutInterface
   */
  private $entityLayout;

  /**
   * The entity layout manager class.
   *
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * The entity layout service class.
   *
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * EntityLayoutFormBase constructor.
   *
   * @param EntityLayoutManager $entityLayoutManager
   *   The entity layout manager class.
   * @param EntityLayoutService $entityLayoutService
   *   The entity layout service class.
   */
  public function __construct(EntityLayoutManager $entityLayoutManager, EntityLayoutService $entityLayoutService) {
    $this->entityLayoutManager = $entityLayoutManager;
    $this->entityLayoutService = $entityLayoutService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_layout.manager'),
      $container->get('entity_layout.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_layout_reset_layout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Remove the cancel button if this is an AJAX request since Drupals built
    // in modal dialogues does not handle buttons that are not a primary
    // button very well.
    if ($this->getRequest()->isXmlHttpRequest()) {
      unset($form['actions']['cancel']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content_entity = $this->getContentEntityFromRouteMatch();

    if ($this->entityLayoutService->resetContentEntityLayout($this->contentEntity)) {
      drupal_set_message($this->t('The layout for @label has been reset.', [
        '@label' => $content_entity->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The layout for @label could not be reset. Please try again.', [
        '@label' => $content_entity->label(),
      ]), 'warning');
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Resetting the layout will restore it to it\'s original state that it was in after it was first initialized. Once performed this action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $content_entity = $this->getContentEntityFromRouteMatch();
    $entity_type_id = $content_entity->getEntityTypeId();

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $content_entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $content_entity = $this->getContentEntityFromRouteMatch();

    return $this->t('Are you sure you want to reset the layout for @label?', [
      '@label' => $content_entity->label(),
    ]);
  }

  /**
   * Get the entity layout from the route match parameters.
   *
   * @return EntityLayoutInterface
   */
  public function getEntityLayoutFromRouteMatch() {
    if (!$this->entityLayout) {
      $content_entity = $this->getContentEntityFromRouteMatch();

      $this->entityLayout = $this->entityLayoutManager
        ->getEntityLayout($content_entity->getEntityTypeId(), $content_entity->bundle());
    }

    return $this->entityLayout;
  }

  /**
   * Attempt to get a content entity from the route match.
   *
   * @return ContentEntityInterface
   */
  protected function getContentEntityFromRouteMatch() {
    if (!$this->contentEntity) {
      $parameters = $this->getRouteMatch()->getParameters();
      $entity_type_id = $parameters->get('entity_type_id');

      if ($entity_type_id && $parameters->has($entity_type_id)) {
        $this->contentEntity = $parameters->get($entity_type_id);
      }
    }

    return $this->contentEntity;
  }
}
