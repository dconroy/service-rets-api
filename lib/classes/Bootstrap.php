<?php
/**
 * Main Bootstrap file.
 *
 * It loads all stuff and can be used for calling Utility functions.
 * Example: mpo()->get_schema( 'required' );
 *
 * @since 0.1.0
 * @author peshkov@UD
 */
namespace MPO {

  use \Luracast\Restler\Restler;
  use \Luracast\Restler\HumanReadableCache;

  if (!class_exists('MPO\Bootstrap')) {

    class Bootstrap {

      /**
       * @var Singleton The reference to *Singleton* instance of this class
       */
      private static $instance;

      /**
       * Protected constructor to prevent creating a new instance of the
       * *Singleton* via the `new` operator from outside of this class.
       *
       * Do Nothing here!
       * We initialize all stuff in load()!
       *
       * @author peshkov@UD
       */
      protected function __construct() {}

      /**
       * Loader
       *
       * - Initializes REST API.
       *
       */
      protected function load() {
        /**
         * Defines Restler library which handles our API
         *
         * See: https://github.com/Luracast/Restler
         *
         */
        $r = new Restler();

        /**
         * Add json and xml support formats.
         *
         * e.g.:
         * http(s)://{domain}/status/v1
         * http(s)://{domain}/status/v1.xml
         * http(s)://{domain}/status/v1.json
         *
         */
        $r->setSupportedFormats('JsonFormat', 'XmlFormat');

        /**
         * Swagger UI handler.
         * Generates resources.json which is being used by Explorer
         *
         * See: http(s)://{domain}/explorer
         */
        $r->addAPIClass('Luracast\\Restler\\Resources'); //this creates resources.json at API Root

        /**
         * Initialize REST API Access Control
         *
         * See: http://restler3.luracast.com/examples/_010_access_control/readme.html
         */
        $r->addAuthenticationClass('AccessControl');

        /**
         * Enable our APIs
         */
        $r->addAPIClass('MPO\\Status', '');
        $r->addAPIClass('MPO\\Search');
        $r->addAPIClass('MPO\\Meta');

        $r->handle(); //serve the response
      }

      /**
       * Private clone method to prevent cloning of the instance of the
       * *Singleton* instance.
       *
       * @return void
       */
      private function __clone() {}

      /**
       * Private unserialize method to prevent unserializing of the *Singleton*
       * instance.
       *
       * @return void
       */
      private function __wakeup() {}

      /**
       * Returns Singleton object.
       *
       * See: mpo() function
       */
      static public function get_instance() {
        if (null === static::$instance) {
          static::$instance = new static();
          /** Load all stuff here! */
          static::$instance->load();
        }
        return static::$instance;
      }

      /**
       * Determine if Utility class contains missed function
       * in other case, just return NULL to prevent ERRORS
       *
       * Example:
       *
       * mpo()->get_schema();
       *
       * will call
       *
       * MPO\Utility::get_schema();
       *
       * @author peshkov@UD
       * @param $name
       * @param $arguments
       * @return mixed|null
       */
      public function __call($name, $arguments) {
        if (is_callable(array("\\MPO\\Utility", $name)) && method_exists( "\\MPO\\Utility", $name ) ) {
          return call_user_func_array(array("\\MPO\\Utility", $name), $arguments);
        } else {
          return NULL;
        }
      }

    }

  }

}

