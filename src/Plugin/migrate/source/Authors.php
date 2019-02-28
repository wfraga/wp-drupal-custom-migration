<?php

namespace Drupal\wp_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Extract authors from Wordpress site.
 *
 * @MigrateSource(
 *   id = "wordpress_authors"
 * )
 */
class Authors extends SqlBase {

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
    $full_name = $row->getSourceProperty('display_name');
    if (!empty($first_name) and !empty($last_name)) {
      $full_name = $first_name . ' ' . $last_name;
    }

    $row->setSourceProperty('user_full_name', $full_name);
    $row->setSourceProperty('user_position', $extra_infos['user_position']);
    $row->setSourceProperty('user_description', $extra_infos['user_description']);
    $row->setSourceProperty('user_facebook', $extra_infos['user_facebook']);
    $row->setSourceProperty('user_twitter', $extra_infos['user_twitter']);
    $row->setSourceProperty('user_instagram', $extra_infos['user_instagram']);
    $row->setSourceProperty('user_linkedin', $extra_infos['user_linkedin']);
    $row->setSourceProperty('user_youtube', $extra_infos['user_youtube']);
    $row->setSourceProperty('picture_url', 'https://www.drupal.org/files/drupal_logo-blue.png');

    return parent::prepareRow($row);
  }

}
