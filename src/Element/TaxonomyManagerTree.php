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
    );
  }

  public static function processTree(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    if (!empty($element['#vocabulary'])) {
      $taxonomy_vocabulary = \Drupal::entityManager()->getStorage('taxonomy_vocabulary')->load($element['#vocabulary']);
      $pager_size = isset($element['#pager_size']) ? $element['#pager_size']: -1;
      $terms = TaxonomyManagerTree::loadTerms($taxonomy_vocabulary, 0, $pager_size);
      $nested_json_list  = TaxonomyManagerTree::getNestedListJSONArray($terms);

      $element['#attached']['library'][] = 'taxonomy_manager/tree';
      $element['#attached']['drupalSettings']['taxonomy_manager']['tree'][] = array(
        'id' => $element['#id'],
        'name' => $element['#name'],
        'source' => $nested_json_list,
      );

      $element['tree'] = array();
      $element['tree']['#prefix'] = '<div id="'. $element['#id'] .'">';
      $element['tree']['#suffix'] = '</div>';
    }

    return $element;
  }

  /**
   * Load one single level of terms, sorted by weight and alphabet.
   */
  public static function loadTerms($vocabulary, $parent = 0, $pager_size = -1) {
    $database = \Drupal::database();
    if ($pager_size > 0) {
      $query = $database->select('taxonomy_term_data', 'td')->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    }
    else {
      $query = $database->select('taxonomy_term_data', 'td');
    }
    $query->fields('td', array('tid'));
    $query->condition('td.vid', $vocabulary->id());
    $query->join('taxonomy_term_hierarchy', 'th', 'td.tid = th.tid AND th.parent = :parent', array(':parent' => $parent));
    $query->join('taxonomy_term_field_data', 'tfd', 'td.tid = tfd.tid');
    $query->orderBy('tfd.weight', 'DESC');
    $query->orderBy('tfd.name', 'ASC');

    if ($pager_size > 0) {
      $query->limit($pager_size);
    }

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
   *
   * @deprecated Use getNestedListJSONArray instead.
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
   * Helper function that generates the nested list for the JSON array structure.
   */
  public static function getNestedListJSONArray($terms, $recursion = FALSE) {
    $items = array();
    if (!empty($terms)) {
      foreach ($terms as $term) {
        $item = array(
          'title' => $term->getName(),
          'key'=> $term->id(),
        );

        if (isset($term->children) || TaxonomyManagerTree::getChildCount($term->id()) >= 1) {
          // If the given terms array is nested, directly process the terms.
          if (isset($term->children)) {
            $item['children'] = TaxonomyManagerTree::getNestedListRenderArray($term->children, TRUE);
          }
          // It the term has children, but they are not present in the array,
          // mark the item for lazy loading.
          else {
            $item['lazy'] = TRUE;
          }
        }
        $items[] = $item;
      }
    }
    return $items;
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
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Validate that all submitted terms belong to the original vocabulary and
    // are not faked via manual $_POST changes.
    $selected_terms = array();
    if (is_array($input) && !empty($input)) {
      foreach ($input as $tid) {
        $term = \Drupal::entityManager()->getStorage('taxonomy_term')->load($tid);
        if ($term && $term->getVocabularyId() == $element['#vocabulary']) {
          $selected_terms[] = $tid;
        }
      }
    }
    return $selected_terms;
  }


}
