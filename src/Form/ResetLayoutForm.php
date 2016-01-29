<?php

namespace Drupal\entity_layout\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ResetLayoutForm extends EntityConfirmFormBase
{
  /**
   * The current content entity object if one has been loaded.
   *
   * @var ContentEntityInterface
   */
  protected $contentEntity;

  /**
   * @var EntityLayoutInterface
   */
  protected $entity;

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
  protected function getEntityLayout($entity_type_id, $bundle) {
    return $this->entityLayoutManager->getEntityLayout($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $parameters = $route_match->getParameters()->all();

    $entity_type_id = $parameters['entity_type_id'];
    $bundle = $parameters['bundle'];

    // If the page being loaded is for a content entity then we need to use
    // the bundle from the content entity instead as the bundle name since
    // the one supplied in the route parameters is the key for where the
    // bundle is stored in the database only.
    if (isset($parameters[$entity_type_id]) && $parameters[$entity_type_id] instanceof ContentEntityInterface) {
      /** @var ContentEntityInterface $content_entity */
      $content_entity = $parameters[$entity_type_id];
      $bundle = $content_entity->bundle();

      $this->contentEntity = $content_entity;
    }

    return $this->getEntityLayout($entity_type_id, $bundle);
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
    if ($this->entityLayoutService->resetContentEntityLayout($this->contentEntity)) {
      drupal_set_message($this->t('The layout for @label has been reset.', [
        '@label' => $this->contentEntity->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The layout for @label could not be reset. Please try again.', [
        '@label' => $this->contentEntity->label(),
      ]), 'warning');
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity_type_id = $this->contentEntity->getEntityTypeId();

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $this->contentEntity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the layout for @label?', [
      '@label' => $this->contentEntity->label(),
    ]);
  }
}
