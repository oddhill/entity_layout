<?php

namespace Drupal\entity_layout\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BlockFormBase extends FormBase {

  /**
   * The Drupal block manager.
   *
   * @var PluginManagerInterface
   */
  protected $blockManager;

  /**
   * The Drupal context repository.
   *
   * @var ContextRepositoryInterface
   */
  protected $contextRepository;

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
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The Drupal context repository.
   */
  public function __construct(
    PluginManagerInterface $blockManager,
    ContextRepositoryInterface $contextRepository
  )
  {
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
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

    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    // Add available contexts if this is a context aware block.
    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($form_state->getValue(['settings', 'context_mapping'], []));
    }

    $this->persistBlock($this->block);
  }

  /**
   * Wraps the route builder.
   *
   * @return \Drupal\Core\Routing\RouteBuilderInterface
   *   An object for state storage.
   */
  protected static function routeBuilder() {
    return \Drupal::service('router.builder');
  }
}
