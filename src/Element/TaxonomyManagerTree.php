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
      $tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($taxonomy_vocabulary->id(), 0, NULL, TRUE);

      $nested_list = TaxonomyManagerTree::getNestedList($tree);
      $nested_render_list = TaxonomyManagerTree::getNestedListRenderArray($nested_list);

      $element['tree'] = $nested_render_list;
      $element['tree']['#prefix'] = '<div id="tree">';
      $element['tree']['#suffix'] = '</div>';
    }

    return $element;
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
  public static function getNestedListRenderArray($tree, $recursion = FALSE) {
    $items = array();
    if (!empty($tree)) {
      foreach ($tree as $term) {
        $item = array(
          '#markup' => $term->getName(),
        );
        if (isset($term->children)) {
          $item['children'] = array(
            '#theme' => 'taxonomy_manager_tree_item_list',
            '#items' => TaxonomyManagerTree::getNestedListRenderArray($term->children, TRUE),
          );
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
