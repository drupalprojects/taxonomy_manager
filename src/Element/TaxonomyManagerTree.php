<?php
/**
 * @file
 * Contains \Drupal\taxonomy_manager\Element\TaxonomyManagerTree.
 */

namespace Drupal\taxonomy_manager\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Taxonomy Manager Tree Form Element
 *
 * @FormElement("taxonomy_manager_tree")
 */
class TaxonomyManagerTree extends FormElement {

  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processTree')
      ),
      '#element_validate' => array(
        array($class, 'taxonomy_manager_tree_validate')
      ),
    );
  }

  public static function processTree(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    if (!empty($element['#vocabulary'])) {
      $element['#attached']['library'][] = 'taxonomy_manager/tree';

      $taxonomy_vocabulary = \Drupal::entityManager()->getStorage('taxonomy_vocabulary')->load($element['#vocabulary']);
      $terms = TaxonomyManagerTree::loadTerms($taxonomy_vocabulary);
      //$tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($taxonomy_vocabulary->id(), 0, NULL, TRUE);
      //$nested_list = TaxonomyManagerTree::getNestedList($tree);
      $nested_render_list = TaxonomyManagerTree::getNestedListRenderArray($terms);

      $element['tree'] = $nested_render_list;
      $element['tree']['#prefix'] = '<div id="tree">';
      $element['tree']['#suffix'] = '</div>';
    }

    return $element;
  }

  /**
   * Load one single level of terms, sorted by weight and alphabet.
   */
  public static function loadTerms($vocabulary, $parent = 0) {
    $database = \Drupal::database();
    $query = $database->select('taxonomy_term_data', 'td');
    $query->fields('td', array('tid'));
    $query->condition('td.vid', $vocabulary->id());
    $query->join('taxonomy_term_hierarchy', 'th', 'td.tid = th.tid AND th.parent = :parent', array(':parent' => $parent));
    $query->join('taxonomy_term_field_data', 'tfd', 'td.tid = tfd.tid');
    $query->orderBy('tfd.weight', 'DESC');
    $query->orderBy('tfd.name', 'ASC');
    $result = $query->execute();

    $tids = array();
    foreach ($result as $record) {
      $tids[] = $record->tid;
    }

    return \Drupal::entityManager()->getStorage('taxonomy_term')->loadMultiple($tids);
  }

  /**
   * Helper function that transforms a flat taxonomy tree in a nested array.
   */
  public static function getNestedList($tree = array(), $max_depth = NULL, $parent = 0, $parents_index = array(), $depth = 0) {
    foreach ($tree as $term) {
      foreach ($term->parents as $term_parent) {
        if ($term_parent == $parent) {
          $return[$term->id()] = $term;
        }
        else {
          $parents_index[$term_parent][$term->id()] = $term;
        }
      }
    }

    foreach ($return as &$term) {
      if (isset($parents_index[$term->id()]) && (is_null($max_depth) || $depth < $max_depth)) {
        $term->children = TaxonomyManagerTree::getNestedList($parents_index[$term->id()], $max_depth, $term->id(), $parents_index, $depth + 1);
      }
    }

    return $return;
  }

  /**
   * Helper function that generates the nested taxonomy_manager_tree_item_list render array.
   */
  public static function getNestedListRenderArray($terms, $recursion = FALSE) {
    $items = array();
    if (!empty($terms)) {
      foreach ($terms as $term) {
        $item = array(
          '#markup' => $term->getName(),
          '#wrapper_attributes' => array(
            'id' => $term->id(),
          ),
        );

        if (isset($term->children) || TaxonomyManagerTree::getChildCount($term->id()) >= 1) {
          // If the given terms array is nested, directly process the terms.
          if (isset($term->children)) {
            $item['children'] = array(
              '#theme' => 'taxonomy_manager_tree_item_list',
              '#items' => TaxonomyManagerTree::getNestedListRenderArray($term->children, TRUE),
            );
          }
          // It the term has children, but they are not present in the array,
          // mark the item for lazy loading.
          else {
            $item['#wrapper_attributes']['class'][] = 'lazy';
          }
        }
        $items[] = $item;
      }
    }
    if ($recursion) {
      return $items;
    }
    else {
      return array(
        '#theme' => 'taxonomy_manager_tree_item_list',
        '#items' => $items,
      );
    }
  }

  /**
   * @param $tid
   * @return children count
   */
  public static function getChildCount($tid) {
    static $tids = array();

    if (!isset($tids[$tid])) {
      $database = \Drupal::database();
      $query = $database->select('taxonomy_term_hierarchy', 'h');
      $query->condition('h.parent', $tid);
      $tids[$tid] = $query->countQuery()->execute()->fetchField();
    }

    return $tids[$tid];
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

}
