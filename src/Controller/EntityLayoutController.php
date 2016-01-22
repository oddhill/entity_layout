<?php

namespace Drupal\entity_layout\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
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
   * Display a library of blocks that can be added to the context reaction.
   *
   * @param RouteMatch $route_match
   *   The route match object.
   *
   * @return array
   *   The block library render array.
   */
  public function blockLibrary(RouteMatch $route_match) {

    $entity_layout = $this->entityLayoutManager->getFromRouteMatch($route_match);

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

    $blocks = $this->entityLayoutService->getBlocks();

    // Add each block definition to the table.
    foreach ($blocks as $block_id => $block) {
      if (!$entity_layout->blockIsAllowed($block_id)) {
        continue;
      }

      $links = [
        'add' => [
          'title' => $this->t('Place block'),
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

    //$build['#attached']['library'][] = 'context_ui/admin';

    return $build;
  }
}
