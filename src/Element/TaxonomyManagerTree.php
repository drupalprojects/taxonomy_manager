<?php
/**
 * @file
 * Contains \Drupal\taxonomy_manager\Element\TaxonomyManagerTree.
 */

namespace Drupal\taxonomy_manager\Element;

use Drupal\Core\Render\Element;

/**
 * Port for D7 taxonomy_manager_element_info()
 *
 * @TaxonomyManagerTree("taxonomy_manager_tree")
 */

/******************************************
 * TAXONOMY TREE FORM ELEMENT DEFINITION
 *
 * how to use:
 * $form['name'] = array(
 *   '#type' => 'taxonomy_manager_tree',
 *   '#vid' => $vid,
 * );
 *
 * additional parameter:
 *   #pager: TRUE / FALSE,
 *     whether to use pagers (drupal pager, load of nested children, load of siblings)
 *     or to load the whole tree on page generation
 *   #parent: only children on this parent will be loaded
 *   #terms_to_expand: loads and opens the first path of given term ids
 *   #siblings_page: current page for loading pf next siblings, internal use
 *   #default_value: an array of term ids, which get selected by default
 *   #render_whole_tree: set this option to TRUE, if you have defined a parent for the tree and you want
 *      the the tree is fully rendered
 *   #add_term_info: if TRUE, hidden form values with the term id and weight are going to be added
 *   #expand_all: if TRUE, all elements are going to be expanded by default
 *   #multiple: if TRUE the tree will contain checkboxes, otherwise radio buttons
 *   #tree_is_required: use #tree_is_required instead of #required if you are using the tree within an other
 *                      element and don't want that both are internally required, because it might cause that
 *                      error messages are shown twice (see content_taxonomy_tree)
 *   #language lang code if i18n is enabled and multilingual vocabulary
 *
 * defining term operations:
 *   to add values (operations,..) to each term, add a function, which return a form array
 *   'taxonomy_manager_'. $tree_form_id .'_operations'
 *
 * how to retrieve selected values:
 *   selected terms ids are available in validate / submit function in
 *   $form_values['name']['selected_terms'];
 *
 ******************************************/

class TaxonomyManagerTree extends FormElement {

  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'taxonomy_manager_tree_process_elements')
      ),
      '#element_validate' => array(
        array($class, 'taxonomy_manager_tree_validate')
      ),
      '#tree' => TRUE,
      '#theme' => 'taxonomy_manager_tree',
      '#parent' => 0,
      '#siblings_page' => 0,
      '#operations' => "",
      '#default_value' => array(),
      '#multiple' => TRUE,
      '#add_term_info' => TRUE,
      '#required' => FALSE,
      '#expand_all' => FALSE,
      '#render_whole_tree' => FALSE,
      '#search_string' => '',
      '#terms_to_expand' => array(),
      '#terms_to_highlight' => array(),
      '#language' => NULL,
      '#pager' => FALSE,
    );
  }

  /**
   * validates submitted form values
   * checks if selected terms really belong to initial voc, if not --> form_set_error
   *
   * if all is valid, selected values get added to 'selected_terms' for easy use in submit
   *
   * @param $form
   */
  public static function taxonomy_manager_tree_validate($form, &$form_state) {}

  /**
   * Processes the tree form element
   *
   * @param $element
   * @return the tree element
   */
  public static function taxonomy_manager_tree_process_elements($element) {}
}