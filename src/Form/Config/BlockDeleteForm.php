<?php

namespace Drupal\entity_layout\Form\Config;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BlockDeleteForm extends ConfirmFormBase {

  /**
   * The entity layout.
   *
   * @var EntityLayoutInterface
   */
  private $entityLayout;

  /**
   * The content entity.
   *
   * @var ContentEntityInterface
   */
  private $contentEntity;

  /**
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * Constructs a new BlockDeleteForm.
   *
   * @param EntityLayoutManager $entityLayoutManager
   *   The entity layout manager.
   */
  public function __construct(EntityLayoutManager $entityLayoutManager) {
    $this->entityLayoutManager = $entityLayoutManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_layout.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_layout_config_block_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the @label block.', [
      '@label' => 'label',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $content_entity = $this->getContentEntityFromRouteMath();
    //$entity_type_id = $content_entity->getEntityTypeId();

    var_dump($content_entity);

    return Url::fromRoute("entity_layout.{$entity_type_id}.content.layout", [
      $entity_type_id => $content_entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  /**
   * Get a block instance from the route match parameters.
   *
   * @return BlockPluginInterface
   */
  protected function getBlockFromRouteMatch() {
    var_dump($this->getRouteMatch()->getParameter());
    die;
  }

  /**
   * Get the entity layout object from the route match object.
   *
   * @return EntityLayoutInterface
   */
  protected function getEntityLayoutFromRouteMatch() {
    if (!$this->entityLayout) {
      $this->entityLayout = $this->entityLayoutManager
        ->getFromRouteMatch($this->getRouteMatch());
    }

    return $this->entityLayout;
  }

  /**
   * Attempt to get a content entity from the route match.
   *
   * @return ContentEntityInterface|null
   */
  protected function getContentEntityFromRouteMath() {
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
