<?php

namespace Drupal\entity_layout\Form\Content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_layout\Form\BaseBlockFormBase;

abstract class BlockFormBase extends BaseBlockFormBase {

  /**
   * @var ContentEntityInterface
   */
  private $contentEntity;

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
