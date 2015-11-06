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
    $terms = array();
    $parent = $_GET['parent']; // @todo take request object from container

    $term = \Drupal::entityManager()->getStorage('taxonomy_term')->load($parent);
    if ($term) {
      $taxonomy_vocabulary = \Drupal::entityManager()->getStorage('taxonomy_vocabulary')->load($term->getVocabularyId());
      if ($taxonomy_vocabulary) {
        $terms = array();
        foreach (TaxonomyManagerTree::loadTerms($taxonomy_vocabulary, $parent) as $term) {
          $terms[] = array(
            'title' => $term->getName(),
            'key'=> $term->id(),
          );
        }
      }
    }

    return new JsonResponse($terms);
  }

}
