<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Form\TaxonomyManagerForm.
 */

namespace Drupal\taxonomy_manager\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;

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
    $form['#attached']['library'][] = 'taxonomy_manager/taxonomy_manager.css';

    if (TaxonomyManagerHelper::_taxonomy_manager_voc_is_empty($taxonomy_vocabulary->id())) {
      $form['text'] = array(
        '#markup' => $this->t('No terms available'),
      );
      $form[] = \Drupal::formBuilder()->getForm('Drupal\taxonomy_manager\Form\AddTermsToVocabularyForm', $taxonomy_vocabulary);
      return $form;
    }

    /* Toolbar. */
    $form['toolbar'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Toolbar'),
    );

    $form['toolbar']['weight_up'] = array(
      '#type' => 'button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons')),
      '#value' => $this->t('Up'),
      '#theme' => 'no_submit_button',
      '#prefix' => '<div id="taxonomy-manager-toolbar-buttons">',
    );

    $form['toolbar']['weight-down'] = array(
      '#type' => 'button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons')),
      '#value' => $this->t('Down'),
      '#theme' => 'no_submit_button',
    );

    $form['toolbar']['add_show'] = array(
      '#type' => 'button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'add')),
      '#value' => $this->t('Add'),
      '#theme' => 'no_submit_button',
    );

    $form['toolbar']['delete_confirm'] = array(
      '#type' => 'button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'delete')),
      '#value' => $this->t('Delete'),
      '#theme' => 'no_submit_button',
    );

    $form['toolbar']['term_merge_show'] = array(
      '#type' => 'button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'merge')),
      '#value' => $this->t('Term merge'),
      '#theme' => 'no_submit_button',
    );

    $form['toolbar']['move_show'] = array(
      '#type' => 'button',
      '#value' => $this->t('Move'),
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'move')),
      '#theme' => 'no_submit_button',
    );

    $form['toolbar']['export_show'] = array(
      '#type' => 'button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'export')),
      '#value' => $this->t('Export'),
      '#theme' => 'no_submit_button',
    );

    $form['toolbar']['search_show'] = array(
      '#type' => 'button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'search')),
      '#value' => $this->t('Search'),
      '#theme' => 'no_submit_button',
    );

    $form['toolbar']['wrapper'] = array(
      '#type' => 'markup',
      '#markup' => '<div id="taxonomy-manager-toolbar-throbber"></div><div class="clear"></div>',
      '#weight' => 20,
      '#prefix' => '</div>',
    );

    /* Taxonomy manager. */
    $form['taxonomy']['#tree'] = TRUE;

    $form['taxonomy']['manager'] = array(
      '#type' => 'fieldset',
      '#title' => $taxonomy_vocabulary->label() . TaxonomyManagerHelper::_taxonomy_manager_select_all_helpers_markup(),
      '#tree' => TRUE,
    );

    $form['taxonomy']['manager']['top'] = array(
      '#markup' => '',
      '#prefix' => '<div class="taxonomy-manager-tree-top">',
      '#suffix' => '</div>',
    );

    $grippie_image = array(
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
    );

    /* Taxonomy manager tree. */
    $tree = $this->storageController->loadTree($taxonomy_vocabulary->id(), 0, NULL, FALSE);
    /* Temporary yeild of a list. */
    $temp_tree = "<ul>";
    foreach($tree as $key => $term_temp) {
      $temp_tree .= "<li>" . $term_temp->name . "</li>";
    }
    $temp_tree .= "</ul>";
    $form['taxonomy']['manager']['tree'] = array(
      '#type' => 'markup',
      '#markup' => $temp_tree,
    );

    return $form;

  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
