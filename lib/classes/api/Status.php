<?php
/**
 * Status
 *
 * @subpackage api
 * @since 0.1.0
 * @author peshkov@UD
 */

namespace MPO {

  use Proxy\Http\Request;
  use Proxy\Proxy;

  if (!class_exists('MPO\Status')) {

    class Status extends API {

      /**
       * Proxies request to Status service and returns its result
       *
       * @return object
       */
      public function index() {

        $request = Request::createFromGlobals();
        $proxy = new Proxy();

        // Proxy to our internal PM2 process
        $response = $proxy->forward( $request, "http://localhost:3000" );

        // Setup Response Headers
        header(sprintf('HTTP/1.1 %s %s', $response->status, $response->getStatusText()), true, $response->status);
        $headers = $response->headers->all();
        foreach( $headers as $name => $value ){
          if( $name == 'content-type' ) {
            header("{$name}: {$value}", false);
            break;
          }
        }
        // Print content end exit
        echo $response->getContent();
        die();
      }

    }


  }

}
