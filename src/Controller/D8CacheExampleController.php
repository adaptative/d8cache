<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\d8cache\Controller;



use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Description of D8CacheExample
 *
 * @author gauravdhariwal
 */
class D8CacheExampleController extends ControllerBase {

  public function index() {
    $output = array();
    $clear = \Drupal::request()->query->get('clear');
    if ($clear) {
      $this->clearPosts();
    }
    if (!$clear) {
      $start_time = microtime(TRUE);
      $data = $this->loadPosts();
      $end_time = microtime(TRUE);

      $duration = $end_time - $start_time;
      $reload = $data['means'] == 'API' ? 'Reload the page to retrieve the posts from cache and see the difference.' : '';
      $output['duration'] = array(
        '#type' => 'markup',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#markup' => t('The duration for loading the posts has been @duration ms using the @means. @reload',
          array(
            '@duration' => number_format($duration * 1000, 2),
            '@means' => $data['means'],
            '@reload' => $reload
          )),
      );
    }
    
    $cache = \Drupal::cache('d8cache')->get('cache_demo_posts');
 
    if ($cache && $data['means'] == 'cache') {
      $url = new Url('d8cache_demo_page', array(), array('query' => array('clear' => true)));
      $output['clear'] = array(
        '#type' => 'markup',
        '#markup' => $this->l('Clear the cache and try again', $url),
      );
    }

    if (!$cache) {
     
      $url = new Url('d8cache_demo_page');
      $output['populate'] = array(
        '#type' => 'markup',
        '#markup' => $this->l('Try loading again to query the API and re-populate the cache', $url),
      );
    }

    return $output;
  }

  /**
   * Loads a bunch of dummy posts from cache or API
   * @return array
   */
  private function loadPosts() {
    $cache = \Drupal::cache('d8cache')->get('cache_demo_posts');
    
    if ($cache) {
      return array(
        'data' => $cache->data,
        'means' => 'cache',
      );
    }
    else {
      $guzzle = new \GuzzleHttp\Client();
      
      $response = $guzzle->get('http://jsonplaceholder.typicode.com/posts');
      $posts = $response->getBody();
      \Drupal::cache('d8cache')->set('cache_demo_posts', $posts);
      return array(
        'data' => $posts,
        'means' => 'API',
      );
    }
  }

  /**
   * Clears the posts from the cache.
   */
  function clearPosts() {
    if ($cache = \Drupal::cache('d8cache')->get('cache_demo_posts')) {
      \Drupal::cache('d8cache')->delete('cache_demo_posts');
      drupal_set_message('Posts have been removed from cache.', 'status');
    }
    else {
      drupal_set_message('No posts in cache.', 'error');
    }
  }
  
}
