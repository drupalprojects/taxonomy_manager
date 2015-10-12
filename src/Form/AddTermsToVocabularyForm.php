<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Form\AddTermsToVocabularyForm.
 */

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;

/**
 * Form for adding terms to a given vocabulary.
 */
class AddTermsToVocabularyForm extends FormBase {
  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $vocabulary = NULL) {
    $form['voc'] = array('#type' => 'value', "#value" => $vocabulary);
    $form['#attached']['library'][] = 'taxonomy_manager/taxonomy_manager.css';

    $attributes = array();
    if ($hide_form) {
      $form['#attached']['js'][] = array(
        'data' => array('hideForm' => array(array(
          'show_button' => 'edit-add-show',
          'hide_button' => 'edit-add-cancel',
          'div' => 'edit-add'))),
        'type' => 'setting');
      $attributes = array('style' => 'display:none;', 'id' => 'edit-add');
      $form['toolbar']['add_show'] = array(
        //'#type' => 'button',
        '#attributes' => array('class' => 'taxonomy-manager-buttons add'),
        '#value' => $this->t('Add'),
        '#theme' => 'no_submit_button',
      );
    }

    $description = $this->t("If you have selected one or more terms in the tree view, the new terms are automatically children of those.");

    $form['add'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#attributes' => $attributes,
      '#title' => $this->t('Add new terms'),
      '#description' => $description,
    );

    $form['add']['mass_add'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Terms'),
      '#description' => $this->t("One term per line. Child terms can be prefixed with a
        dash '-' (one dash per hierarchy level). Terms that should not become
        child terms and start with a dash need to be wrapped in double quotes.
        <br />Example:<br />
        animals<br />
        -canine<br />
        --dog<br />
        --wolf<br />
        -feline<br />
        --cat"),
      '#rows' => 10,
    );
    $form['add']['add'] = array(
      '#type' => 'submit',
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'add')),
      '#value' => $this->t('Add'),
    );
    $form['add']['cancel'] = array(
      '#type' => 'button',
      '#value' => $this->t('Cancel'),
      '#theme' => 'no_submit_button',
      '#attributes' => array('class' => array('taxonomy-manager-buttons', 'cancel')),
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if form is empty.
    $values = $form_state->getValues();
    if (empty($values['add']['mass_add'])) {
      $form_state->setErrorByName('mass_add', $this->t('You must enter at least 1 term name.'));
    }
  }

  /**
   * Submit handler for adding terms.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term_names_too_long = array();
    $term_names = array();

    $selected_terms = $form_state->getValue(['taxonomy', 'manager', 'tree', 'selected_terms']);
    $parents = isset($selected_terms) ? $selected_terms : array();
    $language = $form_state->getValue(['taxonomy', 'manager', 'top', 'language']);
    $lang = isset($language) ? $language : "";

    $mass_terms = $form_state->getValue(['add', 'mass_add']);
    $vocabulary = $form_state->getValue(['voc']);

    $new_terms = TaxonomyManagerHelper::mass_add_terms($mass_terms, $vocabulary->id(), $parents, $lang, $term_names_too_long);
    foreach ($new_terms as $term) {
      $term_names[] = $term->label();
    }
    if (\Drupal::moduleHandler()->moduleExists('i18n_taxonomy')
      && !empty($lang)
      && i18n_taxonomy_vocabulary_mode($vocabulary->id(), I18N_MODE_TRANSLATE)) {
      drupal_set_message($this->t("Saving terms to language @lang",
        array('@lang' => locale_language_name($language))));
    }
    if (count($term_names_too_long)) {
      drupal_set_message($this->t("Following term names were too long and truncated to 255 characters: %names.",
        array('%names' => implode(', ', $term_names_too_long))), 'warning');
    }
    drupal_set_message($this->t("Terms added: %terms", array('%terms' => implode(', ', $term_names))));
  }

  public function getFormId() {
    return 'taxonomy_manager.add_form';
  }
}
