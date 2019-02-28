<?php

namespace Drupal\wp_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Extract authors from Wordpress site.
 *
 * @MigrateSource(
 *   id = "wordpress_hero_banner"
 * )
 */
class HeroBanner extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $prefix = $this->getPrefix();

    $query = $this->select($prefix . '_posts', 'p');
    $query
      ->fields('p', [
        'ID',
        'post_title'
      ]);

    $query->condition('p.post_status', 'publish');
    $query->condition('p.post_type', 'post');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'ID'            => $this->t('Post ID'),
      'post_title'    => $this->t('Post title')
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
    // Clean WP title.
    $post_title     = $row->getSourceProperty('post_title');
    $new_post_title = $this->cleanWPTitle($post_title);
    $row->setSourceProperty('new_title', $new_post_title);
  }

}
