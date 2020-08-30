<?php

namespace Drupal\taxonomy_tree;

use Drupal\Core\Entity\EntityTypeManager;

/**
 * Loads taxonomy terms in a tree.
 */
class TaxonomyTermTree {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * TaxonomyTermTree constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Loads the tree of a vocabulary.
   *
   * @param string $vocabulary
   *   The name of the vocabulary to load.
   *
   * @return array
   *   The array of taxonomy term objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load($vocabulary): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary);
    $tree = [];
    foreach ($terms as $tree_object) {
      $this->buildTree($tree, $tree_object, $vocabulary);
    }

    return $tree;
  }

  /**
   * Populates a tree array given a taxonomy term tree object.
   *
   * @param array $tree
   *   The tree array to populate.
   * @param object $object
   *   The tree object to add to the tree vocab.
   * @param string $vocabulary
   *   The name of the vocabulary being added.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildTree(array &$tree, $object, string $vocabulary): void {
    if ($object->depth != 0) {
      return;
    }
    $key = 'tid_' . $object->tid;

    $tree[$key] = $object;
    $tree[$key]->children = [];
    $object_children = &$tree[$key]->children;

    $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($object->tid);
    if (!$children) {
      return;
    }

    $child_tree_objects = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid == $child->id()) {
          $this->buildTree($object_children, $child_tree_object, $vocabulary);
        }
      }
    }

    uasort($tree, function ($a, $b) {
        return $a->weight <=> $b->weight;
    });
    uasort($object_children, function ($a, $b) {
        return $a->weight <=> $b->weight;
    });
  }

}
