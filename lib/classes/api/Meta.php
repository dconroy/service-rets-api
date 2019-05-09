<?php
/**
 * Meta
 *
 * @subpackage api
 * @since 0.1.0
 * @author peshkov@UD
 */

namespace MPO {

  use \Luracast\Restler\RestException;

  if (!class_exists('MPO\Meta')) {

    class Meta extends API {

      /**
       * Returns Lookup Values for specific field
       *
       * Returns Lookup Values for specific field
       *
       * @param string $mlsKey Our provider ID, or login string.
       * @param string $resource Name of Resource as defined in meta schema.
       * @param string $field Field seeking details on.
       * @throws RestException
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function lookup( $mlsKey, $resource = "Property", $field ) {;

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey );

        $lookupValues = array();
        $values = $this->rets->GetLookupValues( $resource, $field );
        if( !empty( $values ) ) {
          foreach ( $values as $val ) {
            $lookupValue = array();
            foreach( $val->getXmlElements() as $_meta_key ) {
              if( $_meta_key == "MetadataEntryID" ) continue;
              $lookupValue[ $_meta_key ] = $val->offsetGet( $_meta_key );
            }
            array_push( $lookupValues, $lookupValue );
          }
        }

        return $this->_prepare_response( $lookupValues );
      }

      /**
       * Returns Full Schema
       *
       * Returns Full Schema
       *
       * @param string $mlsKey RETS Provider
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function schema( $mlsKey, $credentials = 'production' ) {;
        $schema = array();

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        // Collection instances for values() method.
        foreach( $this->rets->GetResourcesMetadata() as $resource_id => $_resource ) {

          $schema[$resource_id] = Utility::xml_get_base_model_values( $_resource );
          $schema[$resource_id]['classes'] = array();

          // Loop through available resource classes
          foreach( $_resource->getClasses() as $class_id => $_classification ) {
            $_classification = Utility::xml_get_base_model_values( $_classification );
            $_classification['_classID'] = $class_id;
            array_push($schema[$resource_id]['classes'], $_classification);
          }

        }

        return $this->_prepare_response( $schema );

      }

      /**
       * Returns Meta Schema for specific Class of Resource
       *
       * Returns Meta Schema for specific Class of Resource
       *
       * @param string $mlsKey RETS Provider
       * @param string $resource Resource
       * @param string $class Class
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function meta( $mlsKey, $resource = "", $class = "", $credentials = 'production' ) {;

        $response = array();

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );


        // Collection instances for values() method.
        foreach( $this->rets->GetResourcesMetadata() as $resource_id => $_resource ) {

          if( !empty($resource) && $resource !== $resource_id ) {
            continue;
          }

          $_resource_meta = Utility::xml_get_base_model_values( $_resource );

          // Loop through available resource classes
          foreach( $_resource->getClasses() as $class_id => $_classification ) {

            if( !empty($resource) && $class !== $class_id ) {
              continue;
            }

            // compute all fields in classification.
            $_fields = array();
            foreach( $this->rets->GetTableMetadata( $resource_id, $class_id, 'SystemName' ) as $_field_key => $_field_data ) {
              $field = array();
              // Get all field names then use the stupid offsetGet method.
              foreach( $_field_data->getXmlElements() as $_meta_key ) {
                $field[ $_meta_key ] = $_field_data->offsetGet( $_meta_key );
              }
              $_fields[] = array_filter( $field );
            }

            $response[] = array(
              "dataPath" => $resource_id . '/' . $class_id,
              "resource" => $resource_id,
              "class" =>  $class_id,
              "resourceMeta" => $_resource_meta,
              "classMeta" =>  Utility::xml_get_base_model_values( $_classification ),
              "fields" => $_fields
            );

          }

        }

        return $this->_prepare_response( $response );

      }


      /**
       * Returns Server Information
       *
       * Returns Server Information of particular RETS Provider
       *
       * @param string $mlsKey RETS Provider
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function info( $mlsKey, $credentials = 'production' ) {;

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );
        $systemMeta = $this->rets->GetSystemMetadata();
        $serverInfo = $this->rets->GetServerInformation();

        if( !empty( $serverInfo ) ) {
          $serverInfo = json_decode( json_encode( $serverInfo ), TRUE );
        }

        $response = array(
          'systemID' => $systemMeta->getSystemID(),
          'systemDescription' => $systemMeta->getSystemDescription(),
          'timeZoneOffset' => $systemMeta->getTimeZoneOffset(),
          'comments' => $systemMeta->getComments(),
          'version' => $systemMeta->getVersion(),
        );

        // Since RETS servers may have different meta, we have to add some hardcode

        if( $mlsKey == 'mlsl' && !empty( $serverInfo ) ) {
          $response[ 'currentTimeStamp' ] = $serverInfo[ 'ServerInformation' ][ 'Parameter' ];
        }

        return $this->_prepare_response( $response );

      }

    }

  }

}