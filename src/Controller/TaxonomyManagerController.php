<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Controller\TaxonomyManagerController.
 */

namespace Drupal\taxonomy_manager\Controller;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Url;

/**
 * Controller routines for taxonomy_manager routes.
 */
class TaxonomyManagerController {

    /**
     * List of vocabularies, which link to Taxonomy Manager interface
     *
     * @return array
     *   A render array representing the page.
     */
    public function taxonomy_manager_voc_list() {
        $new_voc_url = Url::fromRoute('entity.taxonomy_vocabulary.add_form');
        $new_voc_admin_link = \Drupal::l(t('Add new vocabulary'), $new_voc_url);

        $edit_voc_url = Url::fromRoute('entity.taxonomy_vocabulary.collection');
        $edit_voc_admin_link = \Drupal::l(t('Edit vocabulary settings'), $edit_voc_url);

        $build = array(
            '#markup' => t('!new_voc_admin_link | !edit_voc_admin_link', array(
                    '!new_voc_admin_link' => $new_voc_admin_link,
                    '!edit_voc_admin_link' => $edit_voc_admin_link
                )
            ),
        );

        $voc_list = array();
        $vocabularies = Vocabulary::loadMultiple();
        foreach ($vocabularies as $vocabulary) {
            $vocabulary_form = Url::fromRoute('taxonomy_manager.admin_vocabulary',
                array('taxonomy_vocabulary' => $vocabulary->id()));
            $voc_list[] = \Drupal::l($vocabulary->id(), $vocabulary_form);
        }
        if (!count($voc_list)) {
            $voc_list[] = array('#markup' => $this->t('No Vocabularies available'));
        }

        $build['vocabularies'] = array(
            '#theme' => 'item_list',
            '#items' => $voc_list,
            '#title' => t('Vocabularies'),
        );

        return $build;
    }

}

