<?php

namespace Drupal\faker_generate;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a various helper functions for content generation.
 */
class FakerGenerate {

  /**
   *
   */
  public static function getUsers($number) {
    $users = array();
    $result = db_query_range("SELECT uid FROM {users}", 0, $number);
    foreach ($result as $record) {
      $users[] = $record->uid;
    }
    return $users;
  }

  /**
   *
   */
  public static function deleteContent($values) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $values, 'IN')
      ->execute();

    if (!empty($nids)) {
      $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
      $nodes = $storage_handler->loadMultiple($nids);
      $storage_handler->delete($nodes);
      drupal_set_message(t('Deleted %count nodes.', array('%count' => count($nids))));
    }
  }
}
