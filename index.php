<?php
/**
 * Loader
 *
 */

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

// Prevent Max Allowed Memory Error on trying to get more than 500 items per request
ini_set('memory_limit', '-1');

define( "ABSPATH", __DIR__ );

/**
 * Fixes Luracast\Restler\Restler::getPath() for Nginx
 * which is used to determine Base Url for Swagger UI
 *
 * @author peshkov@UD
 */
if( !empty( $_SERVER[ 'HTTP_DEBUG' ] ) && !empty( $_SERVER[ 'HTTP_HOST' ] ) ) {
  $http_host = explode( ':', $_SERVER[ 'HTTP_HOST' ] );
  if( !empty( $http_host[0] ) ) {
    $_SERVER[ 'SERVER_NAME' ] = $http_host[0];
  }
  if( !empty( $http_host[1] ) ) {
    $_SERVER[ 'SERVER_PORT' ] = $http_host[1];
  }
} else {
  $_SERVER[ 'SERVER_NAME' ] = getenv( "SWAGGER_SERVER_NAME" ) ? getenv( "SWAGGER_SERVER_NAME" ) : "localhost";
  $_SERVER[ 'SERVER_PORT' ] = getenv( "SWAGGER_SERVER_PORT" ) ? getenv( "SWAGGER_SERVER_PORT" ) : "80";
}

/** Auto load all required classes */
require('vendor/autoload.php');

if( !function_exists( 'mpo' ) ) {
  /**
   * Returns  Singleton Instance
   *
   * author peshkov@UD
   */
  function mpo() {
    return \MPO\Bootstrap::get_instance();
  }
}

/** Initialize. */
mpo();