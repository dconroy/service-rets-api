<?php
/**
 * API
 *
 * @since 0.1.0
 * @author peshkov@UD
 */
namespace MPO {

  use \Luracast\Restler\RestException;

  if (!class_exists('MPO\API')) {

    abstract class API {

      /**
       * RETS Client
       *
       * @var object
       */
      protected $rets;

      /**
       * Constructor
       */
      public function __construct() {}

      /**
       * Prepares response nad disconnects from RETS
       *
       * @param mixed $data
       */
      protected function _prepare_response( $data = 'no response data', $disconnect = false ) {
        if( $disconnect ) {
          $this->_disconnect();
        }
        $response = new \stdClass();
        $response->ok = true;
        $response->data = $data;
        return $response;
      }

      /**
       * Initialise RETS API Client.
       * Connects to RETS provider.
       *
       * @author peshkov@UD
       * @throws RestException
       */
      protected function _connect_to_rets( $mlsKey, $type = 'production' ) {
        $this->rets = new RETS( $mlsKey, $type );
        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }
      }

      /**
       * Disconnect from RETS Server.
       *
       * @author peshkov@UD
       * @throws RestException
       */
      protected function _disconnect() {
        if( is_object( $this->rets ) ) {
          $this->rets->disconnect();
        }
      }

    }

  }

}