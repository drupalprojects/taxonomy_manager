<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Form\MoveTermsForm.
 */

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;

/**
 * Form for deleting given terms.
 */
class MoveTermsForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $selected_terms = array()) {
    if (empty($selected_terms)) {
      $form['info'] = array(
        '#markup' => $this->t('Please select the terms you want to move.'),
      );
      return $form;
    }

    // Cache form state so that we keep the parents in the modal dialog.
    $form_state->setCached(TRUE);
    $form['voc'] = array('#type' => 'value', '#value' => $taxonomy_vocabulary);
    $form['selected_terms']['#tree'] = TRUE;

    $items = array();
    foreach ($selected_terms as $t) {
      $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($t);
      $items[] = $term->getName();
      $form['selected_terms'][$t] = array('#type' => 'value', '#value' => $t);
    }

    $form['terms'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Selected terms to move:')
    );

    // @todo Add autocomplete to select/add parent term.

    $form['keep_old_parents'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Keep old parents and add new ones (multi-parent). Otherwise old parents get replaced.'),
    );

    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Move'),
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $taxonomy_vocabulary = $form_state->getValue('voc');
    $selected_terms = $form_state->getValue('selected_terms');
    $keep_old_parents = $form_state->getValue('keep_old_parents');

    // @todo
    drupal_set_message('Move operation not yet implemented.', 'error');
    $form_state->setRedirect('taxonomy_manager.admin_vocabulary', array('taxonomy_vocabulary' => $taxonomy_vocabulary->id()));

  }

  public function getFormId() {
    return 'taxonomy_manager.move_form';
  }
}
