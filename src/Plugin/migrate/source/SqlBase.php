<?php

namespace Drupal\wp_migration\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

abstract class SqlBase extends DrupalSqlBase {

  /**
   * Wordpress prefix table.
   *
   * @var string
   */
  protected $prefix_table = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration) {
    $this->prefix_table = $this->getPrefix();
  }

  /**
   * Get database table prefix from the migration template.
   */
  protected function getPrefix() {
    return !empty($this->configuration['table_prefix']) ? $this->configuration['table_prefix'] : 'wp';
  }

  /**
   * Get Wordpress post type from the migration template.
   */
  protected function getPostType() {
    return !empty($this->configuration['post_type']) ? $this->configuration['post_type'] : 'post';
  }

  /**
   * Get extra Author infos in the WP database.
   *
   * @param string $id
   *  WP user ID.
   * @return array
   *  A array with user info to be used on migration.
   */
  protected function getAuthorInfosById($id) {
    $query = $this->select($prefix_table . '_usermeta', 'um');
    $query->fields('um', ['meta_key', 'meta_value']);
    $query->condition('um.user_id', $id);

    $result = $query->execute()->fetchAllKeyed();

    $arr_return = [
      'user_first_name' => '',
      'user_last_name' => '',
      'user_description' => '',
      'user_facebook' => '',
      'user_twitter' => '',
      'user_youtube' => '',
      'user_instagram' => '',
      'user_linkedin' => '',
      'user_position' => '',
    ];

    foreach ($result as $key => $value) {
      if ($key == 'first_name' and !empty($value)) {
        $arr_return['user_first_name'] = $this->cleanAuthorDesc($value);
      }
      if ($key == 'last_name' and !empty($value)) {
        $arr_return['user_last_name'] = $this->cleanAuthorDesc($value);
      }
      if ($key == 'description' and !empty($value)) {
        $arr_return['user_description'] = $this->cleanAuthorDesc($value);
      }
      if ($key == 'facebook' and !empty($value)) {
        $arr_return['user_facebook'] = $value;
      }
      if ($key == 'twitter' and !empty($value)) {
        $arr_return['user_twitter'] = $value;
      }
      if ($key == 'instagram' and !empty($value)) {
        $arr_return['user_instagram'] = $value;
      }
      if ($key == 'linkedin' and !empty($value)) {
        $arr_return['user_linkedin'] = $value;
      }
      if ($key == 'youtube' and !empty($value)) {
        $arr_return['user_youtube'] = $value;
      }      
      if ($key == 'position' and !empty($value)) {
        $arr_return['user_position'] = $value;
      }
    }

    return $arr_return;
  }

  /**
   * Returns the the post category by erm id..
   *
   * @param string $id
   *  WP term id. 
   * @param string $type
   *  WP term type.
   */
  protected function getTermNameById($id, $type) {
    $query = $this->select($prefix_table . '_terms', 't');
    $query->leftJoin($prefix_table . '_term_taxonomy', 'tt', 'tt.term_id=t.term_id');
    $query->leftJoin($prefix_table . '_term_relationships', 'tr', 'tr.term_taxonomy_id=tt.term_taxonomy_id');
    $query
    ->fields('t', [
      'name'
    ]);

    $query->condition('tt.taxonomy', $type);
    $query->condition('tr.object_id', $id);
    $result = $query->execute()->fetchCol();

    $return = [];
    foreach ($result as $value) {
      $return[] = html_entity_decode($value);
    }

    return $return;
  }

  /**
   * In this specific case, I needed to map in migration process.
   *
   * @param string $current_term
   *  WP current term.
   */
  protected function getNewCategName($current_term) {

    $new_category = '';
    switch ($current_term[0]) {
      case 'Application Aware SD-WAN':
        $new_category = 'SD-WAN';
        break;
      case 'Network Testing & Benchmarking':
      case 'RAN Planning & Optimization':
        $new_category = '5G';
        break;
      case 'Market Trends & Innovations':
        $new_category = 'News & Innovation';
        break; 
      case 'Communication Service Providers':
      case 'Mobile Network Operators':
      case 'Enterprises and Government':
        $new_category = 'Industry';
        break;
      default:
        $new_category = $current_term[0];
    }

    return $new_category;
        
  }

  /**
   * Removes some unwanted things from the WP author description.
   *
   * @param string $str
   *  Original WP author description.
   */
  protected function cleanAuthorDesc($str) {
    $text_returned = html_entity_decode($str);
    $text_returned = $this->removeWPSmartQuotes($text_returned);

    return  $text_returned;
  }

  /**
   * Removes some unwanted things from the WP post content.
   *
   * @param string $str
   *  Original WP post content.
   */
  protected function cleanWPContent($str) {
    $text_returned = html_entity_decode($str);
    $text_returned = $this->removeWPSmartQuotes($text_returned);
    $text_returned = preg_replace('#\[[^\]]+\]#', '', $text_returned);
    $text_returned = nl2br($text_returned);

    return $text_returned;
  }

  /**
   * Removes some unwanted things from the WP post title.
   *
   * @param string $str
   *  Original WP post title.
   */
  protected function cleanWPTitle($str) {
    $text_returned = html_entity_decode($str);
    $text_returned = $this->removeWPSmartQuotes($text_returned);

    return $text_returned;
  }

  /**
   * Remove WP 'Smart Quotes' from string.
   *
   * @param string $str_input
   *  The original string.
   */
  protected function removeWPSmartQuotes($str_input) {
    $quotes = [
    'â€œ' => '“',
    'â€' => '”',
    'â€™' => '’',
    'â€˜' => '‘',
    'â€”' => '–',
    'â€“' => '—',
    'â€¢' => '-',
    'â€¦' => '…',
    'â€™' => "'",
    '' => '',
    'Â' => ''
    ];

    $str_output = strtr($str_input, $quotes);
    return $str_output;
  }

  /**
   * Get first image from WP Content.
   */
  protected function getImgFromContent($html_str) {

    $img_url = 'public://wp_uploads_legacy/default_image/default_blog_article_featured_image.jpg';

    preg_match('@src="([^"]+)"@' , $html_str, $match);

    if (!empty($match)) {
      $img_temp = $match[1];

      if ($this->checkUrlImg($img_temp)) {

        $pattern = '/-[300, 627, 1024]{3}(.*)\./';
        $transform = preg_replace($pattern, '.', $img_temp);

        if (file_exists($transform)) {
          $img_url = $transform;
        }

      }
    }

    return $img_url;
  }

  /**
   * check if the image is applicable. 
   */
  protected function checkUrlImg($str_input) {

    $ext_image = substr(strrchr($str_input,'.'),1);
    if (!in_array($ext_image, ['jpg', 'jpeg', 'png'])) {
      return FALSE;
    }
    else {
      $match_arr = ['Carousel', 'Carousel', 'Ads', 'Ercom'];
      foreach ($match_arr as $value) {
        if (strpos($str_input, $value)) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

  /**
   * Build old WP url by data from database.
   */
  protected function getOldWPUrl($url_base, $post_date) {
    $old_url = '';
    if (!empty($url_base)) {
      if (($timestamp = strtotime($post_date)) !== false) {
        $day    = date('d', $timestamp);
        $month  = date('m', $timestamp);
        $year   = date('Y', $timestamp);

        $base_time = $year . '/' . $month . '/' .$day;
        $old_url = $base_time . '/' . $url_base;
      }
    }

    return $old_url;
  }

}
