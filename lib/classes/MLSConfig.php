<?php
/**
 * MLS Configuration
 *
 * @since 0.2.0
 * @author peshkov@UDX
 */
namespace MPO {

  /**
   * Prevent double class definition
   */
  if ( !class_exists( 'MPO\MLSConfig' ) ) {

    /**
     * MLS Configuration
     * @package MPO
     */
    class MLSConfig {

      /**
       * MLS Key (ID)
       *
       * @var string
       */
      private $mlsKey;

      /**
       * MLS Configuration
       * @var S3Client|null
       */
      private $config;

      /**
       * @var Cache|null
       */
      protected $cache;

      /**
       *
       * @param $mlsKey string
       */
      public function __construct( $mlsKey ) {
        $this->mlsKey = $mlsKey;
        $this->cache = new Cache();
      }

      /**
       *
       * @param string $key
       * @param bool|false $default
       * @return array|bool|null
       * @throws \Exception
       */
      public function get( $key = '', $default = false ) {
        if(empty($this->mlsKey)) {
          throw new \Exception('mlsKey is not set for MLSConfig');
        }
        if(empty($this->config)) {
          $this->setConfig();
        }
        //** Break if config does not exist */
        if( !is_array( $this->config ) ) {
          throw new \Exception('MLS Config could not be defined');
        }
        if( empty( $key ) ) {
          return $this->config;
        }
        //** Resolve dot-notated key. */
        elseif( strpos( $key, '.' ) ) {
          $current = $this->config;
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
          return isset( $this->config[ $key ] ) ? $this->config[ $key ] : $default;
        }
      }

      /**
       * @throws \Exception
       */
      private function setConfig() {
        $config = null;
        $filename = $this->mlsKey.'.txt';

        // Cache data for 10 minutes
        $data = $this->cache->getOrCreate($filename, array( "max-age" => 600 ), function() {
          return $this->doRequest();
        });

        try {
          $data = json_decode($data, true);
          if(!$data["ok"] || !isset($data['data']) || empty($data['data']['connector']['config'])) {
            throw new \Exception( "Bad response from MLS API" );
          }
          $config = $data['data']['connector']['config'];
        } catch (\Exception $e) {
          throw $e;
        }

        $this->config = Utility::array_filter_recursive($config, function($e){
          return $e!==null;
        });

      }

      /**
       *
       */
      private function doRequest() {
        $body = null;
        $client = new \GuzzleHttp\Client();
        $response = $client->get( mpo()->get_config( 'access.mls_api.url' ) . '/' . $this->mlsKey . '/config', array(
          "headers" => array(
            "x-access-token" => mpo()->get_config( 'access.mls_api.x_access_token' ),
            "x-mls-token" => mpo()->get_config( 'access.mls_api.x_mls_token' )
          )
        ) );
        if( 200 == $response->getStatusCode() ) {
          $body = $response->getBody()->getContents();
        }
        return $body;
      }

    }
  }
}