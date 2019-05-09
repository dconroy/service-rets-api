<?php
/**
 * RETS Client
 *
 * @since 0.1.0
 * @author peshkov@UD
 */
namespace MPO {

  use Aws\S3\Exception\S3Exception;

  if (!class_exists('MPO\RETS')) {

    class RETS {

      private $errors = array();

      protected $client;

      /**
       *
       * @param string $mlskey
       * @param string $type
       */
      public function __construct( $mlsKey, $type = 'production' ) {

        try {

          if( empty( $mlsKey ) || !is_string( $mlsKey ) ) {
            throw new \Exception('not passed mlsKey');
          }

          $config = mpo()->get_mls_config( $mlsKey, 'credentials.' . $type );

          if( empty( $config ) ) {
            throw new \Exception('not valid mlsKey');
          }

          date_default_timezone_set('America/New_York');

          $this->client = new Session($config, $mlsKey);
          $this->client->Login( false );

        } catch ( \Exception $e ) {

          array_push( $this->errors, $e->getMessage() );

        }

      }

      /**
       * Disconnect from RETS Server
       */
      public function disconnect() {
        try {
          $this->client->Disconnect();
        } catch ( \Exception $e ) {
          array_push( $this->errors, $e->getMessage() );
        }
      }

      /**
       * Returns Objects for specific property.
       *
       * @param string $resource
       * @param string $type
       * @param string $id
       * @param string $object_ids
       * @param int $location
       * @param bool $cdn
       * @return array|bool
       */
      public function get_object( $resource, $type, $id, $object_ids = '*', $location = 0, $cdn = false, $mlsKey = 'default' ) {
        $data = array();
        try {
          $results = $this->client->GetObject( $resource, $type, $id, $object_ids, $location );
          foreach( $results as $result ) {
            $item = array(
              'content_id' => $result->getContentId(),
              'object_id' => $result->getObjectId(),
              'content_type' => $result->getContentType(),
              'mime' => $result->getMimeVersion(),
              'content_description' => $result->getContentDescription(),
              'is_preferred' => $result->isPreferred(),
              'length' => $result->getSize()
            );
            if( $location ) {
              $content = $result->getLocation();
              if( empty( $content ) || strlen( $content ) < 5 ) {
                continue;
              }
              $item['location'] = $result->getLocation();
            } else {
              $content = $result->getContent();
              $content = trim( $content );
              $content = str_replace( array( '\r', '\n' ), '', $content );
              if( strlen( $content ) < 100 ) {
                continue;
              }
              if( $cdn ) {
                $aws = new AWS();
                $uploaded = $aws->media_upload( $result, strtolower($mlsKey), strtolower($resource) );
                if( !empty( $uploaded['error'] ) ) {
                  throw new Exception( $uploaded['error'] );
                }
                if( empty( $uploaded['ObjectURL'] ) ) {
                  throw new Exception( "There was an issue on trying to upload media to Amazon S3." );
                }
                $item['location'] = $uploaded['ObjectURL'];
              } else {
                $item['data'] =  base64_encode( $result->getContent() );
              }
            }
            array_push( $data, $item );
          }
        } catch ( \Exception $e ) {
          $this->errors[] = $e->getMessage();
          return false;
        }
        return $data;
      }

      /**
       *
       *
       * @param string $resource_id
       * @param string $class_id
       * @param int $page
       * @param int $limit
       * @param string $query
       * @return bool|\PHRETS\Models\Search\Results
       */
      public function search( $resource_id, $class_id, $page = 1, $limit = 10, $query = '' ) {

        $data = array();

        try {
          $results = $this->client->Search( $resource_id, $class_id, $query, array(
            'Limit' => $page > 1 ? $page * $limit + $limit : $limit,
          ) );

        } catch ( \Exception $e ) {
          $this->errors[] = $e->getMessage();
          return false;
        }

        $data[ 'total' ] = $results->getTotalResultsCount();

        $results = $results->toArray();

        $results = array_slice( $results, ( $page * $limit - $limit ) );
        if( count( $results ) > $limit) {
          $results = array_slice( $results, 0, $limit - count( $results ) );
        }

        $data[ 'results' ] = $this->prepare_results( $results, $resource_id );

        return $data;
      }

      /**
       *
       *
       * @return bool
       */
      public function has_errors() {
        return !empty( $this->errors );
      }

      /**
       *
       *
       * @return string
       */
      public function get_errors() {
        return implode( '; ', $this->errors );
      }

      /**
       *
       */
      public function prepare_results( $results, $resource_id ) {
        return $results;
      }

      /**
       * Determine if RETS client class contains missed method
       * in other case, just return NULL to prevent ERRORS
       *
       * @author peshkov@UD
       */
      public function __call( $name, $arguments ) {
        if ( is_callable( array( $this->client, $name) ) ) {
          return call_user_func_array( array( $this->client, $name), $arguments );
        } else {
          return NULL;
        }
      }

    }

  }

}

