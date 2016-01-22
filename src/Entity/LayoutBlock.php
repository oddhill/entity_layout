<?php

namespace Drupal\entity_layout\Entity;

/**
 * Defines the node entity class.
 *
 * @ContentEntityType(
 *   id = "entity_layout_block",
 *   label = @Translation("Entity layout block"),
 *   handlers = {
 *     "storage" = "Drupal\node\NodeStorage",
 *     "storage_schema" = "Drupal\node\NodeStorageSchema",
 *     "view_builder" = "Drupal\node\NodeViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\node\NodeForm",
 *       "delete" = "Drupal\node\Form\NodeDeleteForm",
 *       "edit" = "Drupal\node\NodeForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\node\Entity\NodeRouteProvider",
 *     },
 *     "list_builder" = "Drupal\node\NodeListBuilder",
 *   },
 *   base_table = "entity_layout_blocks",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 * )
 */
class LayoutBlock
{

}
