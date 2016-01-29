<?php

namespace Drupal\entity_layout;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;

class EntityLayoutViewBuilder implements EntityViewBuilderInterface {

  /**
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * @var
   */
  protected $entityTypeManager;

  /**
   * @return RouteMatchInterface
   */
  protected function getRouteMatch() {
    if (!$this->routeMatch) {
      $this->routeMatch = \Drupal::service('current_route_match');
    }

    return $this->routeMatch;
  }

  /**
   * @return EntityLayoutService
   */
  protected function getEntityLayoutService() {
    if (!$this->entityLayoutService) {
      $this->entityLayoutService = \Drupal::service('entity_layout.service');
    }

    return $this->entityLayoutService;
  }

  /**
   * @return EntityTypeManagerInterface
   */
  protected function getEntityTypeManager() {
    if (!$this->entityTypeManager) {
     $this->entityTypeManager = \Drupal::service('entity.manager');
    }

    return $this->entityTypeManager;
  }

  /**
   * Get the content entity object for the entity page being viewed.
   *
   * @return ContentEntityInterface
   */
  protected function getContentEntity() {
    $parameters = $this->getRouteMatch()->getParameters()->all();
    return $parameters[$parameters['entity_type_id']];
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    /** @var EntityLayoutInterface $entity */

    $build = [];

    $cacheability = CacheableMetadata::createFromRenderArray($build);
    $content_entity = $this->getContentEntity();

    if ($this->getEntityLayoutService()->hasContentBlocks($entity, $content_entity)) {
      $blocks = $this->getEntityLayoutService()->getContentBlockCollection($content_entity);
    }
    else {
      $blocks = $entity->getDefaultBlocks();
    }

    //var_dump(\Drupal::service('context.repository')->getAvailableContexts());

    /** @var BlockPluginInterface $block */
    foreach ($blocks as $block_id => $block) {
      $configuration = $block->getConfiguration();

      //var_dump($block->getPluginId());
      //var_dump($block->getContextMapping());

      // Inject runtime contexts.
      if ($block instanceof ContextAwarePluginInterface) {
        $contexts = \Drupal::service('context.repository')->getRuntimeContexts($block->getContextMapping());
        \Drupal::service('context.handler')->applyContextMapping($block, $contexts);
      }

      // Create the render array for the block as a whole.
      // @see template_preprocess_block().
      $block_build = [
        '#theme' => 'block',
        '#attributes' => [],
        '#configuration' => $configuration,
        '#plugin_id' => $block->getPluginId(),
        '#base_plugin_id' => $block->getBaseId(),
        '#derivative_plugin_id' => $block->getDerivativeId(),
        '#block_plugin' => $block,
        '#pre_render' => [[$this, 'preRenderBlock']],
        '#cache' => [
          'keys' => ['entity_layout', 'block', $block_id, $entity->id()],
          'tags' => $block->getCacheTags(),
          'contexts' => $block->getCacheContexts(),
          'max-age' => $block->getCacheMaxAge(),
        ],
      ];

      if (array_key_exists('weight', $configuration)) {
        $block_build['#weight'] = $configuration['weight'];
      }

      $build[$block_id] = $block_build;

      // The main content block cannot be cached: it is a placeholder for the
      // render array returned by the controller. It should be rendered as-is,
      // with other placed blocks "decorating" it. Analogous reasoning for the
      // title block.
      if ($block instanceof MainContentBlockPluginInterface || $block instanceof TitleBlockPluginInterface) {
        unset($build[$block_id]['#cache']['keys']);
      }

      $cacheability->addCacheableDependency($block);
    }

    $cacheability->applyTo($build);

    //die;

    return $build;
  }

  /**
   * Renders the content using the provided block plugin.
   *
   * @param  array $build
   * @return array
   */
  public function preRenderBlock($build) {

    $content = $build['#block_plugin']->build();

    unset($build['#block_plugin']);

    // Abort rendering: render as the empty string and ensure this block is
    // render cached, so we can avoid the work of having to repeatedly
    // determine whether the block is empty. E.g. modifying or adding entities
    // could cause the block to no longer be empty.
    if (is_null($content) || Element::isEmpty($content)) {
      $build = [
        '#markup' => '',
        '#cache' => $build['#cache'],
      ];

      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    else {
      $build['content'] = $content;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = array(), $view_mode = 'full', $langcode = NULL) {
    $build = [];

    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = array()) {
    throw new \LogicException();
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = array()) {
    throw new \LogicException();
  }
}
