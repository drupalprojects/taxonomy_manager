<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 05.11.15
 * Time: 18:13
 */

namespace Drupal\taxonomy_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\taxonomy_manager\Element\TaxonomyManagerTree;

class TaxonomyManagerSubTreeController extends ControllerBase {

  public function json() {
    $list = array();
    $parent = \Drupal::request()->get('parent');

    $term = \Drupal::entityManager()->getStorage('taxonomy_term')->load($parent);
    if ($term) {
      $taxonomy_vocabulary = \Drupal::entityManager()->getStorage('taxonomy_vocabulary')->load($term->getVocabularyId());
      if ($taxonomy_vocabulary) {
        $terms = TaxonomyManagerTree::loadTerms($taxonomy_vocabulary, $parent);
        $list = TaxonomyManagerTree::getNestedListJSONArray($terms);
      }
    }

    return new JsonResponse($list);
  }

}
