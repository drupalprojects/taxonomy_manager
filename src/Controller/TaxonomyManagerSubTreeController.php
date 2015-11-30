<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Controller\TaxonomyManagerSubTreeController.
 */

namespace Drupal\taxonomy_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\taxonomy_manager\Element\TaxonomyManagerTree;

class TaxonomyManagerSubTreeController extends ControllerBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a TaxonomyManagerSubTreeController object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * JSON callback for subtree.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function json() {
    $list = array();
    $parent = $this->request->get('parent');

    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($parent);
    if ($term) {
      $taxonomy_vocabulary = $this->entityTypeManager()->getStorage('taxonomy_vocabulary')->load($term->getVocabularyId());
      if ($taxonomy_vocabulary) {
        $terms = TaxonomyManagerTree::loadTerms($taxonomy_vocabulary, $parent);
        $list = TaxonomyManagerTree::getNestedListJSONArray($terms);
      }
    }

    return new JsonResponse($list);
  }

}
