<?php

namespace Drupal\taxonomy_manager;

use Drupal\Core\Language\LanguageInterface;

class TaxonomyManagerHelper {
  /**
   * checks if voc has terms
   *
   * @param $vid voc id
   * @return true, if terms already exists, else false
   */
  public static function _taxonomy_manager_voc_is_empty($vid) {
    $has_rows = (bool) db_query_range("SELECT 1 FROM {taxonomy_term_data} t INNER JOIN {taxonomy_term_hierarchy} h ON t.tid = h.tid WHERE vid = :vid AND h.parent = 0", 0, 1, array(':vid' => $vid))->fetchField();
    return !$has_rows;
  }

  /**
   * Helper function for mass adding of terms.
   *
   * @param $input
   *   The textual input with terms. Each line contains a single term. Child term
   *   can be prefixed with a dash '-' (one dash for each level). Term names
   *   starting with a dash and should not become a child term need to be wrapped
   *   in quotes.
   * @param $vid
   *   The vocabulary id.
   * @param int $parents
   *   An array of parent term ids for the new inserted terms. Can be 0.
   * @param $lang
   *   The i18n language, if i18n exists.
   * @param $term_names_too_long
   *   Return value that is used to indicate that some term names were too long
   *   and truncated to 255 characters.
   *
   * @return An array of the newly inserted term objects
   */
  public static function mass_add_terms($input, $vid, $parents, $lang = "", &$term_names_too_long = array()) {
    $new_terms = array();
    $terms = explode("\n", str_replace("\r", '', $input));
    $parents = count($parents) ? $parents : 0;

    // Stores the current lineage of newly inserted terms.
    $last_parents = array();
    foreach ($terms as $name) {
      if (empty($name)) {
        continue;
      }
      $matches = array();
      // Child term prefixed with one or more dashes
      if (preg_match('/^(-){1,}/', $name, $matches)) {
        $depth = strlen($matches[0]);
        $name = substr($name, $depth);
        $current_parents = isset($last_parents[$depth-1]) ? $last_parents[$depth-1]->tid : 0;
      }
      // Parent term containing dashes at the beginning and is therefore wrapped
      // in double quotes
      elseif (preg_match('/^\"(-){1,}.*\"/', $name, $matches)) {
        $name = substr($name, 1, -1);
        $depth = 0;
        $current_parents = $parents;
      }
      else {
        $depth = 0;
        $current_parents = $parents;
      }
      // Truncate long string names that will cause database exceptions.
      if (strlen($name) > 255) {
        $term_names_too_long[] = $name;
        $name = substr($name, 0, 255);
      }

      $filter_formats = filter_formats();
      $format = array_pop($filter_formats);
      $settings = [
        'name' => $name,
        'parent' => $current_parents, //@TODO: to be fixed.
        'format' => $format->id(),
        'vid' => $vid,
//      if (\Drupal::moduleHandler()->moduleExists('i18n_taxonomy') && !empty($lang) && i18n_taxonomy_vocabulary_mode($vid, I18N_MODE_TRANSLATE)) {
//        $term->language = $lang;
//      }
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ];
      $term = entity_create('taxonomy_term', $settings);
      $term->save();
      $new_terms[] = $term;
      $last_parents[$depth] = $term;
    }
    return $new_terms;
  }

  /**
   * Returns html markup for (un)select all checkboxes buttons.
   * @return string
   */
  public static function _taxonomy_manager_select_all_helpers_markup() {
    return '<span class="taxonomy-manager-select-helpers">' .
    '<span class="select-all-children" title="' . t("Select all") . '">&nbsp;&nbsp;&nbsp;&nbsp;</span>' .
    '<span class="deselect-all-children" title="' . t("Remove selection") . '">&nbsp;&nbsp;&nbsp;&nbsp;</span>' .
    '</span>';
  }

}