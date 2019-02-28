<?php

namespace Drupal\wp_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Extract content from Wordpress site.
 *
 * @MigrateSource(
 *   id = "wordpress_content"
 * )
 */
class Content extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $prefix = $this->getPrefix();
    $post_type = $this->getPostType();

    $query = $this->select($prefix . '_posts', 'p');
    $query
      ->fields('p', [
        'ID',
        'post_date',
        'post_title',
        'post_content',
        'post_excerpt',
        'post_modified',
        'post_name',
        'post_author',
        'post_name'
      ]);

    $query->condition('p.post_status', 'publish');
    $query->condition('p.post_type', $post_type);
    $query->orderBy("p.post_date", 'DESC');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'ID'            => $this->t('Post ID'),
      'post_title'    => $this->t('Title'),
      'post_excerpt'  => $this->t('Excerpt'),
      'post_content'  => $this->t('Content'),
      'post_date'     => $this->t('Created Date'),
      'post_modified' => $this->t('Modified Date'),
      'post_author'   => $this->t('Post Author'),
      'post_name'     => $this->t('URL base'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'ID' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    $post_id        = $row->getSourceProperty('ID');
    $post_title     = $row->getSourceProperty('post_title');
    $post_content   = $row->getSourceProperty('post_content');
    $url_base       = $row->getSourceProperty('post_name');
    $post_date       = $row->getSourceProperty('post_date');

    $tags_names     = $this->getTermNameById($post_id, 'post_tag');
    // Get new categories mapped from old categories.
    $categ_names    = $this->getTermNameById($post_id, 'category');
    $categ_new_name = $this->getNewCategName($categ_names);
    // Remove extra codes from content text.
    $new_content    = $this->cleanWPContent($post_content, 'content');
    // Clean WP title.
    $new_post_title = $this->cleanWPTitle($post_title);
    $old_url = $this->getOldWPUrl($url_base, $post_date);

    $row->setSourceProperty('tags_names', $tags_names);
    $row->setSourceProperty('categ_names', $categ_new_name);
    $row->setSourceProperty('new_content', $new_content);
    $row->setSourceProperty('new_title', $new_post_title);
    $row->setSourceProperty('old_url', $old_url);

    return parent::prepareRow($row);
  }

}
