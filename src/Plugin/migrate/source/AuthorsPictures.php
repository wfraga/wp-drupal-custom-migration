<?php

namespace Drupal\wp_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Extract authors from Wordpress site.
 *
 * @MigrateSource(
 *   id = "wordpress_authors_pictures"
 * )
 */
class AuthorsPictures extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $prefix = $this->getPrefix();

    $query = $this->select($prefix . '_users', 'u');
    $query->distinct();
    $query->innerJoin($prefix . '_posts', 'p', 'u.ID=p.post_author');
    $query
      ->fields('u', [
        'ID',
        'display_name'
      ]);

    $query->condition('p.post_author', 1, '<>');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'ID'            => $this->t('Author ID'),
      'display_name'    => $this->t('User name')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'ID' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

    /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    $author_id = $row->getSourceProperty('ID');
    $extra_infos = $this->getAuthorInfosById($author_id);
    $first_name = $extra_infos['user_first_name'];
    $last_name = $extra_infos['user_last_name'];
    $file_name = $first_name . '_' . $last_name;

    $picture_path = 'public://author_blog_pictures/wp_author_pictures/nophoto.png';
    $local_path = 'public://author_blog_pictures/wp_author_pictures/';
    $path_temp = $local_path . $file_name;
    $exts = ['jpg', 'jpeg', 'png', 'gif'];
    foreach ($exts as $ext) {
      if (file_exists($path_temp . '.' . $ext)) {
        $picture_path = $path_temp . '.' . $ext;
        break;
      }
    }

    $row->setSourceProperty('picture_url', $picture_path);
  }

}
