<?php
/**
 * Utility functions
 *
 * @since 0.1.0
 * @author peshkov@UD
 */
namespace MPO {

  if (!class_exists('MPO\Utility')) {

    class Utility {

      /**
       * @var array
       */
      static $config;

      /**
       * @var \MPO\MLSConfig
       */
      static $MLSConfig = array();

      /**
       * Returns specific schema from config.json file.
       *
       * @param string $key Deep key to element in composer.json
       * @param mixed $default The value which should be returned in case data is not found.
       * @author peshkov@UD
       * @return mixed
       */
      static function get_config( $key = '', $default = false ) {
        if( self::$config === null ) {
          /**
           * PATH TO CONFIG.JSON
           */
          $path = dirname( dirname( __DIR__ ) ) . '/config.json';
          if( file_exists( $path ) ) {
            self::$config = json_decode( file_get_contents( $path ), true );
          }
        }
        //** Break if config.json does not exist */
        if( !is_array( self::$config ) ) {
          return false;
        }
        if( empty( $key ) ) {
          return self::$config;
        }
        //** Resolve dot-notated key. */
        elseif( strpos( $key, '.' ) ) {
          $current = self::$config;
          $p = strtok( $key, '.' );
          while( $p !== false ) {
            if( !isset( $current[ $p ] ) ) {
              return false;
            }
            $current = $current[ $p ];
            $p = strtok( '.' );
          }
          return $current;
        }
        //** Get default key */
        else {
          return isset( self::$config[ $key ] ) ? self::$config[ $key ] : $default;
        }
      }

      /**
       * Returns MLS Configuration
       *
       * @param string $mls
       * @param string $key Deep key to element
       * @param boolean $default
       * @return array|null
       * @throws \Exception
       */
      static function get_mls_config( $mls, $key = '', $default = false ) {
        if( empty( self::$MLSConfig[$mls] ) ) {
          self::$MLSConfig[$mls] = new MLSConfig( $mls );
        }
        return self::$MLSConfig[$mls]->get( $key, $default );
      }

      /**
       * Parses value and replaces data if needed.
       *
       * @author peshkov@UD
       * @return string
       */
      static function parse_query_value( $value ) {

        // Replace #today# with current date.
        if( strpos( $value, '#today#' ) !== false ) {
          $now = date( 'Y-m-d' ) . 'T00:00:00';
          $value = str_replace( '#today#', $now, $value );
        }

        return $value;
      }

      /**
       * The phprets Models make it hard to get protected values.
       * This helper iterates over attributes and returns a simple array of all of them.
       *
       * @param $_instance
       * @return array
       */
      static function xml_get_base_model_values( $_instance ) {
        $_result = array();
        if ( method_exists( $_instance, 'getXmlElements' ) && method_exists( $_instance, 'offsetGet' ) ) {
          foreach ( $_instance->getXmlElements() as $_key ) {
            $_result[ $_key ] = $_instance->offsetGet( $_key );
          }
        }
        return $_result;
      }

      /**
       * Filters elements of an array using a callback function
       * http://php.net/manual/en/function.array-filter.php
       *
       * @param $input
       * @param $callback
       * @return array
       */
      static function array_filter_recursive($input, $callback = null ) {
        foreach ($input as &$value)  {
          if (is_array($value)) {
            $value = self::array_filter_recursive($value, $callback);
          }
        }
        return array_filter($input, $callback);
      }

    }

  }

}

