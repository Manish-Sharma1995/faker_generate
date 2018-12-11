<?php

namespace Drupal\faker_generate;

use Faker;
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

  public static function generateContent($values, &$context)  {

    $faker = Faker\Factory::create();
    $name = $faker->name;

    if (!isset($values['settings']['time_range'])) {
      $values['settings']['time_range'] = 0;
    }
    $content_types = $values['settings']['node_types'];
    $num = $values['settings']['num'];

    $users = FakerGenerate::getUsers($num);

    if (!empty($values['settings']['del']) && array_filter($content_types)) {
      FakerGenerate::deleteContent(array_filter($content_types));
    }

    $results = array();

    for ($i = 1; $i <= $num; $i++) {

      $content_type = array_rand(array_filter($content_types));
      $uid = $users[array_rand($users)];
      // Creating a node...
      $node = Node::create([
        'nid' => NULL,
        'type' => $content_type,
        'title' => $name,
        'uid' => $uid,
        'revision' => mt_rand(0, 1),
        'status' => TRUE,
        'promote' => mt_rand(0, 1),
        'created' => REQUEST_TIME - mt_rand(0, $values['settings']['time_range']),
        'langcode' => 'en',
        'body' => $faker->realText($maxNbChars = 300, $indexSize = 2),
      ]);
      $entityManager = \Drupal::service('entity_field.manager');
      $fields = $entityManager->getFieldDefinitions('node', $content_type);
      foreach ($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          $bundleFields[$field_name]['type'] = $field_definition->getType();
          $bundleFields[$field_name]['label'] = $field_definition->getLabel();
          switch($bundleFields[$field_name]['type'])  {
            case 'email':
              $node->set($field_definition->getName(), $faker->email);
              break;
            case 'image':
              $image = $faker->image('sites/default/files', $width = 640, $height = 480);
              $data = file_get_contents($image);
              $file = file_save_data($data, "public://sample.png", FILE_EXISTS_REPLACE);
              $node->set($field_definition->getName(), [
                'target_id' => $file->id(),
                'alt' => 'Random Image',
                'title' => 'Some Random Image'
              ]);
              break;
            case 'datetime':
              $node->set($field_definition->getName(), $faker->date());
              break;
          }
          //\Drupal::logger('fake_generator')->notice('Type: ' . $bundleFields[$field_name]['type']);
        }
      }
      $results[] = $node->save();
    }
    $context['message'] = 'Creating node..';
    $context['results'] = $results;
  }

  function nodesGeneratedFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One node created.', '@count nodes created.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
