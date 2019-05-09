<?php
/**
 * File Cache
 *
 * @since 0.3.1
 * @author peshkov@UDX
 */
namespace MPO {

  /**
   * Prevent double class definition
   */
  if ( !class_exists( 'MPO\Cache' ) ) {

    /**
     * File Cache
     * @package MPO
     */
    class Cache {

      /**
       * @var object
       */
      private $cache;

      /**
       *
       */
      public function __construct() {
        $this->cache = new \Gregwar\Cache\Cache;
        $this->cache->setCacheDirectory('cache');
        $this->cache->setPrefixSize(1);
      }

      public function __call($name, $arguments) {
        if (is_callable(array($this->cache, $name)) && method_exists( $this->cache, $name ) ) {
          return call_user_func_array(array($this->cache, $name), $arguments);
        } else {
          throw new \Exception( "Method ".$name." does not exist" );
        }
      }

    }
  }
}