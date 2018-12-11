<?php

namespace Drupal\faker_generate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\faker_generate\FakerGenerate;
use Faker;

/**
 * Provides a form for bulk deletion of users.
 */
class FakerGenerateContentForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'faker_generate_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_types_list = [];
    $node_types = \Drupal::service('entity.manager')
        ->getStorage('node_type')
        ->loadMultiple();
    foreach ($node_types as $node_type) {
        $node_types_list[$node_type->id()] = $node_type->label();
    }
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content Generate Settings'),
      '#tree' => TRUE,
      // '#attributes' => ['class' => ['container-inline']],
    ];
    $form['settings']['node_types'] = [
      '#title' => $this->t('Content type'),
      '#type' => 'checkboxes',
      '#options' => $node_types_list,
    ];
    $form['settings']['del'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all content</strong> in these content types before generating new content.'),
      '#default_value' => FALSE,
    ];
    $form['settings']['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many nodes would you like to generate?'),
      '#default_value' => 50,
      '#required' => TRUE,
      '#min' => 0,
    ];

    $options = array(1 => $this->t('Now'));
    foreach (array(3600, 86400, 604800, 2592000, 31536000) as $interval) {
      $options[$interval] = \Drupal::service('date.formatter')->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }
    $form['settings']['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How far back in time should the nodes be dated?'),
      '#description' => $this->t('Node creation dates will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    ];

    // $form['settings']['max_comments'] = [
    //   '#type' => \Drupal::service('module_handler')->moduleExists('comment') ? 'number' : 'value',
    //   '#title' => $this->t('Maximum number of comments per node.'),
    //   '#description' => $this->t('You must also enable comments for the content types you are generating. Note that some nodes will randomly receive zero comments. Some will receive the max.'),
    //   '#default_value' => 0,
    //   '#min' => 0,
    //   '#access' => \Drupal::service('module_handler')->moduleExists('comment'),
    // ];
    // $form['settings']['title_length'] = [
    //   '#type' => 'number',
    //   '#title' => $this->t('Maximum number of words in titles'),
    //   '#default_value' => 2,
    //   '#required' => TRUE,
    //   '#min' => 1,
    //   '#max' => 255,
    // ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!array_filter($values['settings']['node_types'])) {
      $form_state->setErrorByName('node_types', $this->t('Please select at least one content type'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    //FakerGenerate::generateContent($form_state->getValues());

    $batch = array(
      'init_message' => t('Executing batch process...'),
      'title' => t('Creating nodes'),
      'operations' => array(
                            array(
                                '\Drupal\faker_generate\FakerGenerate::generateContent',
                                array($form_state->getValues())
                            ),
                          ),
      'finished' => '\Drupal\faker_generate\FakerGenerate::nodesGeneratedFinishedCallback',
    );
    batch_set($batch);
  }
}
