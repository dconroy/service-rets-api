<?php
namespace MPO {

  use GuzzleHttp\Client;
  use GuzzleHttp\Cookie\CookieJar;
  use GuzzleHttp\Cookie\CookieJarInterface;
  use Illuminate\Support\Collection;
  use PHRETS\Exceptions\CapabilityUnavailable;
  use PHRETS\Exceptions\MetadataNotFound;
  use PHRETS\Exceptions\MissingConfiguration;
  use PHRETS\Exceptions\RETSException;
  use PHRETS\Http\Client as PHRETSClient;
  use PHRETS\Interpreters\GetObject;
  use PHRETS\Interpreters\Search;
  use PHRETS\Models\Bulletin;

  class Session extends \PHRETS\Session {

    /**
     * MLS Provider ( see config.json )
     *
     * @var string
     */
    protected $mlsKey;

    /**
     * Are we trying to retry the existing Session or create new one (Login)
     *
     * @var string
     */
    protected $newSession;

    /**
     * @param \PHRETS\Configuration $configuration
     * @throws \PHRETS\Exceptions\InvalidConfiguration
     */
    public function __construct( $configuration, $mlsKey = null ) {
      parent::__construct( \PHRETS\Configuration::load($configuration) );

      if( !empty( $configuration['server_information_url'] ) ) {
        $this->capabilities->add('ServerInformation', $configuration['server_information_url'] );
      }

      $this->mlsKey = $mlsKey;

    }

    public function isNewSession() {
      return $this->newSession;
    }

    /**
     *
     * @param $force
     */
    public function Login( $force = true ) {
      // New Session
      if( $force || !$this->mlsKey ) {
        //echo "<pre>";print_r( "New Session" );echo "</pre>";
        $this->newSession = true;
        $this->_deleteSessionData();
        parent::Login();
        // Get all RETS session cookies
        $cookies = array();
        $cookiesIterator = $this->cookie_jar->getIterator();
        while( $cookiesIterator->valid()) {
          array_push( $cookies, $cookiesIterator->current()->toArray() );
          $cookiesIterator->next();
        }
        // Get all RETS session capabilities
        $capabilities = array_filter( array(
          "GetMetadata" => $this->capabilities->get( "GetMetadata" ),
          "GetObject" => $this->capabilities->get( "GetObject" ),
          "Search" => $this->capabilities->get( "Search" ),
          "Logout" => $this->capabilities->get( "Logout" )
        ) );
        $this->_putSessionData( array(
          "cookies" => $cookies,
          "capabilities" => $capabilities
        ) );
      }
      // Try to use existing Session
      else {
        $data = $this->_getSessionData();
        if( !$data ) {
          return $this->Login( true );
        }
        $this->newSession = false;
        //echo "<pre>";print_r( "Existing Session" );echo "</pre>";
        foreach( $data['cookies'] as $cookie ) {
          $this->cookie_jar->setCookie( new \GuzzleHttp\Cookie\SetCookie( $cookie ) );
        }
        foreach( $data['capabilities'] as $k => $v ) {
          $this->capabilities->add( $k , $v );
        }
      }
      return;
    }

    /**
     * @param $resource_id
     * @param $class_id
     * @param $dmql_query
     * @param array $optional_parameters
     * @return \PHRETS\Models\Search\Results
     * @throws \PHRETS\Exceptions\CapabilityUnavailable
     */
    public function Search($resource_id, $class_id, $dmql_query, $optional_parameters = [], $recursive = false) {

      try {

        $dmql_query = \PHRETS\Interpreters\Search::dmql($dmql_query);

        $defaults = [
          'SearchType' => $resource_id,
          'Class' => $class_id,
          'Query' => $dmql_query,
          'QueryType' => 'DMQL2',
          'Count' => 1,
          'Format' => 'COMPACT-DECODED',
          'Limit' => 99999999,
          'StandardNames' => 0,
        ];

        $parameters = array_merge($defaults, $optional_parameters);

        // if the Select parameter given is an array, format it as it needs to be
        if (array_key_exists('Select', $parameters) and is_array($parameters['Select'])) {
          $parameters['Select'] = implode(',', $parameters['Select']);
        }

        $response = $this->request(
          'Search',
          [
            'timeout' => 180,
            'connect_timeout' => 180,
            'query' => $parameters
          ]
        );

        if ($recursive) {
          $parser = $this->grab('parser.search.recursive');
        } else {
          $parser = $this->grab('parser.search');
        }

        $results = $parser->parse($this, $response, $parameters);

      } catch ( \Exception $e ) {
        if( ltrim( get_class( $e ), '\\' ) == 'GuzzleHttp\Exception\XmlParseException' ) {
          $parser = new Parser();
          $results = $parser->parse( $this, $response, $parameters );
        } else {
          //** It must never happen. But */
          throw new \Exception( $e->getMessage() );
        }
      }

      return $results;

    }

    /**
     * @param $capability
     * @param array $options
     * @throws \PHRETS\Exceptions\CapabilityUnavailable
     * @throws \PHRETS\Exceptions\RETSException
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    protected function request($capability, $options = []) {
      $_options = $options;

      $url = $this->capabilities->get($capability);

      if (!$url) {
        throw new CapabilityUnavailable("'{$capability}' tried but no valid endpoint was found.  Did you forget to Login()?");
      }

      if (!array_key_exists('headers', $options)) {
        $options['headers'] = [];
      }

      // Guzzle 5 changed the order that headers are added to the request, so we'll do this manually
      $options['headers'] = array_merge($this->client->getDefaultOption('headers'), $options['headers']);

      // user-agent authentication
      if ($this->configuration->getUserAgentPassword()) {
        $ua_digest = $this->configuration->userAgentDigestHash($this);
        $options['headers'] = array_merge($options['headers'], ['RETS-UA-Authorization' => 'Digest ' . $ua_digest]);
      }

      $options = array_merge($options, ['cookies' => $this->cookie_jar]);

      $this->debug("Sending HTTP Request for {$url} ({$capability})", $options);

      if (array_key_exists('query', $options)) {
        $this->last_request_url = $url . '?' . \http_build_query($options['query']);
      } else {
        $this->last_request_url = $url;
      }

      try {
        /** @var \GuzzleHttp\Message\ResponseInterface $response */
        if ($this->configuration->readOption('use_post_method')) {
          $this->debug('Using POST method per use_post_method option');
          $query = (array_key_exists('query', $options)) ? $options['query'] : null;
          $response = $this->client->post($url, array_merge($options, ['body' => $query]));
        } else {
          $response = $this->client->get($url, $options);
        }
      } catch ( \Exception $e ) {
        if( in_array( $capability, array( "Login", "Logout" ) ) || $this->isNewSession() ) {
          throw $e;
        }
        // Try to RE-Login and retry our request again
        $this->cookie_jar->clear();
        $this->Login( true );
        return $this->request( $capability, $_options );
      }

      $this->last_response = $response;

      $cookie = $response->getHeader('Set-Cookie');
      if ($cookie) {
        if (preg_match('/RETS-Session-ID\=(.*?)(\;|\s+|$)/', $cookie, $matches)) {
          $this->rets_session_id = $matches[1];
        }
      }

      if ($response->getHeader('Content-Type') == 'text/xml' and $capability != 'GetObject') {
        $xml = $response->xml();
        if ($xml and isset($xml['ReplyCode'])) {
          $rc = (string)$xml['ReplyCode'];
          // 20201 - No records found - not exception worthy in my mind
          if ($rc != "0" and $rc != "20201") {
            throw new RETSException($xml['ReplyText'], (int)$xml['ReplyCode']);
          }
        }
      }

      $this->debug('Response: HTTP ' . $response->getStatusCode());

      return $response;
    }

    /**
     * @return null|\SimpleXMLElement
     * @throws \PHRETS\Exceptions\CapabilityUnavailable
     * @throws \PHRETS\Exceptions\RETSException
     */
    public function GetServerInformation() {
      $xml = null;
      if( $this->capabilities->get( 'ServerInformation' ) ) {
        $response = $this->request( 'ServerInformation' );
        $xml = $response->xml();
      }
      return $xml;
    }

    /**
     *
     *
     * @param $data array
     */
    protected function _putSessionData( $data ) {
      $dir = "";
      if( defined( "ABSPATH" ) && is_dir( ABSPATH ) ) {
        $dir .=  ABSPATH;
      } else {
        $dir .= __DIR__;
      }
      $dir .=  DIRECTORY_SEPARATOR . "tmp";
      if( !is_dir( $dir )  ) {
        @mkdir($dir);
      }
      @file_put_contents( ( $dir . DIRECTORY_SEPARATOR . $this->mlsKey . ".tmp" ), json_encode( $data ) );
    }

    /**
     *
     */
    protected function _getSessionData() {
      $path = "";
      if( defined( "ABSPATH" ) && is_dir( ABSPATH ) ) {
        $path .=  ABSPATH;
      } else {
        $path .= __DIR__;
      }
      $path .=  DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR . $this->mlsKey . ".tmp";
      if( !is_file( $path )  ) {
        return false;
      }

      // Be sure Session was created more than 5 minutes ago
      $modified = filemtime( $path );
      if((time() - $modified) > 300 ) {
        unlink( $modified );
        return false;
      }

      $data = file_get_contents( $path );
      if( empty( $data ) ) {
        return false;
      }
      $data = @json_decode( $data, true );
      if( !is_array( $data ) || empty( $data ) || empty( $data[ 'cookies' ] ) || empty( $data[ 'capabilities' ] )  ) {
        return false;
      }
      return $data;
    }

    /**
     *
     */
    protected function _deleteSessionData() {
      $path = "";
      if( defined( "ABSPATH" ) && is_dir( ABSPATH ) ) {
        $path .=  ABSPATH;
      } else {
        $path .= __DIR__;
      }
      $path .=  DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR . $this->mlsKey . ".tmp";
      if( is_file( $path )  ) {
        unlink( $path );
      }
      return;
    }

  }

}
