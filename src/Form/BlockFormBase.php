<?php

namespace Drupal\entity_layout\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BlockFormBase extends FormBase {

  /**
   * The Drupal block manager.
   *
   * @var PluginManagerInterface
   */
  protected $blockManager;

  /**
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * The Drupal context repository.
   *
   * @var ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The entity layout associated with the entity that the block is added for.
   *
   * @var EntityLayoutInterface
   */
  protected $entityLayout;

  /**
   * A content entity if it exists.
   *
   * @var ContentEntityInterface
   */
  protected $contentEntity;

  /**
   * The block plugin instance.
   *
   * @var BlockPluginInterface
   */
  protected $block;

  /**
   * Constructs a new BlockFormBase.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $blockManager
   *   The Drupal block manager.
   * @param EntityLayoutManager $entityLayoutManager
   *   The entity layout manager.
   * @param EntityLayoutService $entityLayoutService
   *   The entity layout service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The Drupal context repository.
   */
  public function __construct(
    PluginManagerInterface $blockManager,
    EntityLayoutManager $entityLayoutManager,
    EntityLayoutService $entityLayoutService,
    ContextRepositoryInterface $contextRepository
  )
  {
    $this->blockManager = $blockManager;
    $this->entityLayoutManager = $entityLayoutManager;
    $this->entityLayoutService = $entityLayoutService;
    $this->contextRepository = $contextRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('entity_layout.manager'),
      $container->get('entity_layout.service'),
      $container->get('context.repository')
    );
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
  abstract protected function prepareBlock($block_id);

  /**
   * Function to handle persisting of the block once saved.
   *
   * @param  BlockPluginInterface $block
   * @return mixed
   */
  abstract protected function persistBlock(BlockPluginInterface $block);

  /**
   * Get the URL object for the redirect.
   *
   * @return Url
   */
  abstract protected function getRedirectUrl();

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param null $block_id
   *   The id of the block to place.
   *
   * @return array The form structure.
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $block_id = NULL) {
    $this->entityLayout = $this->getEntityLayoutFromRouteMatch();
    $this->contentEntity = $this->getContentEntityFromRouteMath();
    $this->block = $this->prepareBlock($block_id);

    // Some blocks require contexts, set a temporary value with gathered
    // contextual values.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $form['#tree'] = TRUE;

    $form['settings'] = $this->block->buildConfigurationForm([], $form_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save block'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create a new form state for the block configuration form.
    $settings = (new FormState())->setValues($form_state->getValue('settings'));

    // Call the plugin submit handler.
    $this->block->submitConfigurationForm($form, $settings);

    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    // Add available contexts if this is a context aware block.
    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($form_state->getValue(['settings', 'context_mapping'], []));
    }

    $this->persistBlock($this->block);

    $form_state->setRedirectUrl($this->getRedirectUrl());
  }

  /**
   * Get the entity layout object from the route match object.
   *
   * @return EntityLayoutInterface
   */
  protected function getEntityLayoutFromRouteMatch() {
    return $this->entityLayoutManager->getFromRouteMatch($this->getRouteMatch());
  }

  /**
   * Attempt to get a content entity from the route match.
   *
   * @return ContentEntityInterface|null
   */
  protected function getContentEntityFromRouteMath() {
    $parameters = $this->getRouteMatch()->getParameters();
    $entity_type_id = $parameters->get('entity_type_id');

    if ($entity_type_id && $parameters->has($entity_type_id)) {
      return $parameters->get($entity_type_id);
    }

    return NULL;
  }
}
