<?php

namespace Drupal\entity_layout\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\entity_layout\EntityLayoutInterface;
use Drupal\entity_layout\EntityLayoutManager;
use Drupal\entity_layout\EntityLayoutService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityLayoutController extends ControllerBase {

  /**
   * The entity layout service class.
   *
   * @var EntityLayoutService
   */
  protected $entityLayoutService;

  /**
   * The entity layout manager.
   *
   * @var EntityLayoutManager
   */
  protected $entityLayoutManager;

  /**
   * EntityLayoutController constructor.
   *
   * @param EntityLayoutManager $entityLayoutManager
   *   The entity layout manager.
   * @param EntityLayoutService $entityLayoutService
   *   The entity layout service class.
   */
  public function __construct(EntityLayoutManager $entityLayoutManager, EntityLayoutService $entityLayoutService) {
    $this->entityLayoutService = $entityLayoutService;
    $this->entityLayoutManager = $entityLayoutManager;
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
   * Build block library for a content entity.
   *
   * @param RouteMatch $route_match
   *   The route match object.
   *
   * @return array
   */
  public function contentBlockLibrary(RouteMatch $route_match) {
    $parameters = $route_match->getParameters();

    $entity_type_id = $parameters->get('entity_type_id');

    /** @var ContentEntityInterface $content_entity */
    $content_entity = $parameters->get($entity_type_id);

    $entity_layout = $this->entityLayoutManager->getEntityLayout($entity_type_id, $content_entity->bundle());

    return $this->buildBlockLibrary($entity_layout, $content_entity);
  }

  /**
   * Build block library for a config entity.
   *
   * @param RouteMatch $route_match
   *   The route match object.
   *
   * @return array
   */
  public function configBlockLibrary(RouteMatch $route_match) {
    $entity_layout = $this->entityLayoutManager->getFromRouteMatch($route_match);
    return $this->buildBlockLibrary($entity_layout);
  }

  /**
   * Build the render array for the block library.
   *
   * @param EntityLayoutInterface $entity_layout
   *   The entity layout to show allowed blocks for.
   * @param ContentEntityInterface $content_entity
   *   The content entity the block is being added to.
   *
   * @return array
   *   The render array.
   */
  protected function buildBlockLibrary(EntityLayoutInterface $entity_layout, ContentEntityInterface $content_entity = NULL) {

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['context-table-filter'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $headers = [
      $this->t('Block'),
      $this->t('Category'),
      $this->t('Operations'),
    ];

    $build['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => [],
      '#empty' => $this->t('No blocks available for placement.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    $blocks = $this->entityLayoutService->getSystemBlocks();

    // Add each block definition to the table.
    foreach ($blocks as $block_id => $block) {
      if (!$entity_layout->blockIsAllowed($block_id)) {
        continue;
      }

      $bundle_entity_type = $this->entityLayoutService
        ->getTargetBundleEntityType($entity_layout);

      // Use different routes depending on what kind of entity were adding
      // blocks for.
      if ($content_entity) {
        $entity_type_id = $content_entity->getEntityTypeId();

        $block_add_url = Url::fromRoute("entity_layout.{$entity_type_id}.content.block.add", [
          'block_id' => $block_id,
          $entity_type_id => $content_entity->id(),
        ]);
      }
      else {
        $entity_type_id = $entity_layout->getTargetEntityType();

        $block_add_url = Url::fromRoute("entity_layout.{$entity_type_id}.block.add", [
          'block_id' => $block_id,
          $bundle_entity_type => $entity_layout->getTargetBundle(),
        ]);
      }

      $links = [
        'add' => [
          'title' => $this->t('Place block'),
          'url' => $block_add_url,
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 700,
            ]),
          ],
        ],
      ];

      $build['blocks']['#rows'][] = [
        'title' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<div class="context-table-filter-text-source">{{ label }}</div>',
            '#context' => [
              'label' => $block['admin_label'],
            ],
          ],
        ],
        'category' => [
          'data' => $block['category'],
        ],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ],
      ];
    }

    // @todo Create a filter behaviour for the table.
    //$build['#attached']['library'][] = 'context_ui/admin';

    return $build;
  }
}
