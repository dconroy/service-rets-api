<?php
/**
 * AWS Client
 *
 * @since 0.1.0
 * @author korotkov@UD
 */
namespace MPO {

  /**
   * Use AWS S3 Client
   */
  use Aws\S3\Exception\S3Exception;
  use Aws\S3\S3Client;

  /**
   * Prevent double class definition
   */
  if ( !class_exists( 'MPO\AWS' ) ) {

    /**
     * Class AWS wrapper
     * @package MPO
     */
    class AWS {

      /**
       * Client storage
       * @var S3Client|null
       */
      private $client = null;

      /**
       * Bucket name
       */
      private $bucket = null;

      /**
       * Init
       */
      public function __construct() {

        if ( !$this->client ) {
          $config = mpo()->get_config('access.s3.client');
          $this->set_client( $config );
        }

        if ( !$this->bucket ) {
          $this->set_bucket( mpo()->get_config('access.s3.bucket') );
        }
      }

      /**
       * Bucket setter
       * @param $bucket
       */
      public function set_bucket( $bucket ) {
        $this->bucket = $bucket;
      }

      /**
       * Bucket getter
       * @return null
       */
      public function get_bucket() {
        return $this->bucket;
      }

      /**
       * Client setter
       * @param $config
       */
      public function set_client( $config ) {
        $this->client = new S3Client($config);
      }

      /**
       * Client getter
       * @return S3Client|null
       */
      public function get_client() {
        return $this->client;
      }

      /**
       * @param $data object
       * @param $mlsKey string
       * @param $resource string
       * @return string
       */
      public function media_upload( $data, $mlsKey = "default", $resource = "default" ) {
        try {
          $result = $this->get_client()->putObject(array(
            'Bucket' => $this->get_bucket(),
            'Key'    => 'mls/'.$mlsKey.'/'.$resource.'/'.$data->getContentId().'/'.md5( $data->getContent() ),
            'Body'   => $data->getContent(),
            'ACL'    => 'public-read',
            'ContentType' => $data->getContentType()
          ));
          // Print the URL to the object.
          return $result;
        } catch (S3Exception $e) {
          return array( 'error' => $e->getMessage() );
        }

      }
    }
  }
}