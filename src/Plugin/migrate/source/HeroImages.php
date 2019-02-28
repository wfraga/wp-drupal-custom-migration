<?php

namespace Drupal\wp_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Extract authors from Wordpress site.
 *
 * @MigrateSource(
 *   id = "wordpress_hero_images"
 * )
 */
class HeroImages extends SqlBase {

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
        'post_content'
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
      'ID' => $this->t('Author ID'),
      'post_content' => $this->t('Post content')
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

    $post_content   = $row->getSourceProperty('post_content');
    $new_content    = $this->cleanWPContent($post_content);
    $content_img_path = $this->getImgFromContent($new_content);
    $row->setSourceProperty('content_img_path', $content_img_path);

    return parent::prepareRow($row);
  }

}
