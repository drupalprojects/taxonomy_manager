<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Form\TaxonomyManagerForm.
 */

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;
use Drupal\Component\Utility\HTML;

class TaxonomyManagerForm extends FormBase {

  public function getFormId() {
    return 'taxonomy_manager.vocabulary_terms_form';
  }

  /**
   * The term storage controller.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $storageController;

  /**
   * Constructs an OverviewTerms object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->storageController = $entity_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Returns the title for the whole page
   * @param String $taxonomy_vocabulary the name of the vocabulary
   * @return string The title, itself
   */
  public function getTitle($taxonomy_vocabulary) {
    return  $this->t("Taxonomy Manager - %voc_name", array("%voc_name" => $taxonomy_vocabulary->label()));
  }

  /**
   * Form constructor.
   *
   * Display a tree of all the terms in a vocabulary, with options to edit
   * each one. The form implements the Taxonomy Manager intefrace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary being with worked with
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $form['voc'] = array('#type' => 'value', "#value" => $taxonomy_vocabulary);
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    if (TaxonomyManagerHelper::_taxonomy_manager_voc_is_empty($taxonomy_vocabulary->id())) {
      $form['text'] = array(
        '#markup' => $this->t('No terms available'),
      );
      $form[] = \Drupal::formBuilder()->getForm('Drupal\taxonomy_manager\Form\AddTermsToVocabularyForm', $taxonomy_vocabulary);
      return $form;
    }

    $form['toolbar'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Toolbar'),
    );
    $form['toolbar']['add'] = array(
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => $this->t('Add'),
      '#ajax' => array(
        'callback' => '::addFormCallback',
      ),
    );
    $form['toolbar']['delete'] = array(
      '#type' => 'submit',
      '#name' => 'delete',
      '#value' => $this->t('Delete'),
      '#ajax' => array(
        'callback' => '::deleteFormCallback',
      ),
    );
    $form['toolbar']['move'] = array(
      '#type' => 'submit',
      '#name' => 'move',
      '#value' => $this->t('Move'),
      '#ajax' => array(
        'callback' => '::moveFormCallback',
      ),
    );

    /* Taxonomy manager. */
    $form['taxonomy']['#tree'] = TRUE;

    $form['taxonomy']['manager'] = array(
      '#type' => 'fieldset',
      '#title' => HTML::escape($taxonomy_vocabulary->label()),
      '#tree' => TRUE,
    );

    $form['taxonomy']['manager']['top'] = array(
      '#markup' => '',
      '#prefix' => '<div class="taxonomy-manager-tree-top">',
      '#suffix' => '</div>',
    );

    /*$grippie_image = array(
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'taxonomy_manager') . "/images/grippie.png",
      '#alt' => $this->t("Resize tree"),
      '#title' => $this->t("Resize tree"),
      '#attributes' => array('class' => array('div-grippie')),
    );

    $form['taxonomy']['manager']['top']['size'] = array(
      '#markup' =>
        '<div class="taxonomy-manager-tree-size">'
        . \Drupal::service('renderer')->render($grippie_image, true)
        . '</div>'
    );*/

    $form['taxonomy']['manager']['tree'] = array(
      '#type' => 'taxonomy_manager_tree',
      '#vocabulary' => $taxonomy_vocabulary->id(),
      '#pager_size' => \Drupal::config('taxonomy_manager.settings')->get('taxonomy_manager_pager_tree_page_size'),
    );

    $form['taxonomy']['manager']['pager'] = array('#type' => 'pager');

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_terms = $form_state->getValue(['taxonomy', 'manager', 'tree']);
    //dsm($selected_terms);
  }

  /**
   * AJAX callback handler for add form.
   */
  public function addFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\AddTermsToVocabularyForm', 'taxonomy_manager.admin_vocabulary.add', $this->t('Add terms'));

  }

  /**
   * AJAX callback handler for delete form.
   */
  public function deleteFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\DeleteTermsForm', 'taxonomy_manager.admin_vocabulary.delete', $this->t('Delete terms'));
  }

  /**
   * AJAX callback handler for move form.
   */
  public function moveFormCallback($form, FormStateInterface $form_state) {
    return $this->modalHelper($form_state, 'Drupal\taxonomy_manager\Form\MoveTermsForm', 'taxonomy_manager.admin_vocabulary.move', $this->t('Move terms'));
  }

  /**
   * Helper function to generate a modal form within an AJAX callback.
   *
   * @param $form_state
   *   The form state of the current (parent) form.
   * @param $class_name
   *   The class name of the form to embed in the modal.
   * @param $route_name
   *   The route name the form is located.
   * @param $title
   *   The modal title.
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  protected function modalHelper($form_state, $class_name, $route_name, $title) {
    $taxonomy_vocabulary = $form_state->getValue('voc');
    $selected_terms = $form_state->getValue(['taxonomy', 'manager', 'tree']);

    $del_form = \Drupal::formBuilder()->getForm($class_name, $taxonomy_vocabulary, $selected_terms);
    $del_form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    // Change the form action url form the current site to the add form.
    $del_form['#action'] = $this->url($route_name, array('taxonomy_vocabulary' => $taxonomy_vocabulary->id()));

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $del_form, array('width' => '700')));
    return $response;
  }

}
