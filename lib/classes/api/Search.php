<?php
/**
 * Sync
 *
 * @subpackage api
 * @since 0.1.0
 * @author peshkov@UD
 */

namespace MPO {

  use \Luracast\Restler\RestException;

  if (!class_exists('MPO\Search')) {

    class Search extends API {

      /**
       * Returns Media
       *
       * Returns the list of Media ( Photos ) data for specified listing.
       *
       * @param string $mlsKey RETS Provider
       * @param string $sourceID Source ID
       * @param string $resource Resource
       * @param string $type Type
       * @param int $page Page
       * @param int $limit Limit
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function media( $mlsKey, $sourceID, $resource = '', $type = '', $page = 1, $limit = 3, $credentials = "production" ) {

        $config = mpo()->get_mls_config( $mlsKey, "resources" );
        if( !$config['media']['enabled'] ) {
          throw new RestException( 400, 'Resource is not enabled for the current MLS' );
        }

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        if( !is_numeric( $page ) || $page < 1 ) {
          $page = 1;
        }
        if( empty( $limit ) || !is_numeric( $limit ) ) {
          $limit = 3;
        }

        $object_ids = '';
        $start = $page > 1 ? ( $page - 1 ) * $limit + 1 : $page;
        for( $i = $start; $i < ( $start + $limit ); $i++ ) {
          $object_ids .= $i . ',';
        }
        $object_ids = rtrim( $object_ids, ',' );

        $location = $config['media']['enabledLocation'] ? 1 : 0;

        if( !$resource ) {
          $resource = $config['property']['resource'];
        }

        /* If object Type is not provided we get object Type from config */
        if( !$type ) {
          $type = $config['media']['objectType'];
        }

        $data = $this->rets->get_object( $resource, $type, $sourceID, $object_ids, $location, true, $mlsKey );
        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }

        return $this->_prepare_response( $data );
      }

      /**
       * Returns Search results
       *
       * Returns Search results
       *
       * @param string $mlsKey RETS Provider
       * @param string $resource Resource ID
       * @param $class
       * @param int $page Page
       * @param int $limit Limit
       * @param mixed $query DMQL Query
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @internal param string $classID RETS Class
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function search( $mlsKey, $resource, $class, $page = 1, $limit = 10, $query, $credentials = "production" ) {;

        if( !is_numeric( $page ) || $page < 1 ) {
          $page = 1;
        }
        if( empty( $limit ) || !is_numeric( $limit ) ) {
          $limit = 10;
        }

        /* Get resource ID for Property from our config file. */
        $resource_id = $resource;
        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        $data = $this->rets->search( $resource_id, $class, $page, $limit, $query );
        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }

        return $this->_prepare_response( array(
          'query' => $query,
          'page' => $page,
          'limit' => $limit,
          'total' => $data[ 'total' ],
          'results' => $data[ 'results' ],
        ) );
      }

      /**
       * Returns Search results
       *
       * Returns Found Properties for specified RETS Provider ( mlsKey ).
       * If DMQL query is not provided, default DQML Query will be used.<br/><br/>
       * Note, even if DQML query provided it extends it by default query since RETS always requires specific attributes for search.<br/><br/>
       * For example:<br/>
       * SFAR provider requires the following combination of attributes to search: Status and SearchPrice,
       * so DQML query will always include: <code>(Status=|A),(SearchPrice=0+)</code><br/>
       * However you can redefine values of Status and SearchPrice in provided DMQL Query
       *
       * @param string $mlsKey RETS Provider
       * @param $class
       * @param int $page Page
       * @param int $limit Limit
       * @param mixed $query DMQL Query
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @internal param string $classID RETS Class
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function property( $mlsKey, $class, $page = 1, $limit = 10, $query = '', $credentials = "production" ) {;

        $config = mpo()->get_mls_config( $mlsKey, "resources" );
        if( !$config['property']['enabled'] ) {
          throw new RestException(400, 'Resource is not enabled for the current MLS' );
        }

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        if( !is_numeric( $page ) || $page < 1 ) {
          $page = 1;
        }
        if( empty( $limit ) || !is_numeric( $limit ) ) {
          $limit = 10;
        }

        /* Get resource ID for Property from MLS config. */
        $resource_id = $config['property']['resource'];

        /* Set DMQL Query */
        $query = $this->_get_dmql_query( $query, $mlsKey, 'property' );

        $data = $this->rets->search( $resource_id, $class, $page, $limit, $query );
        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }

        return $this->_prepare_response( array(
          'query' => $query,
          'page' => $page,
          'limit' => $limit,
          'total' => $data[ 'total' ],
          'primary_key' => $config['property']['metadata']['primaryKey'],
          'date_key' => $config['property']['metadata']['lastModifiedDateTime'],
          'media_location' => $config['media']['enabledLocation'],
          'results' => $data[ 'results' ],
        ) );
      }

      /**
       * Returns Agent results
       *
       * Returns Found Agents for specified RETS Provider ( mlsKey ).
       *
       * * <code>(LicenseType=|R)</code> - Real Estate agents only.
       *
       * @param string $mlsKey RETS Provider
       * @param int $page Page
       * @param int $limit Limit
       * @param mixed $query DMQL Query
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @internal param string $classID RETS Class
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function agent( $mlsKey, $page = 1, $limit = 10, $query = '', $credentials = "production" ) {;

        $config = mpo()->get_mls_config( $mlsKey, "resources" );
        if( !$config['agent']['enabled'] ) {
          throw new RestException(400, 'Resource is not enabled for the current MLS' );
        }

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        if( !is_numeric( $page ) || $page < 1 ) {
          $page = 1;
        }
        if( empty( $limit ) || !is_numeric( $limit ) ) {
          $limit = 10;
        }

        /* Get resource ID for Agent from MLS config. */
        $resource_id = $config['agent']['resource'];
        $default_class = $config['agent']['class'][0];

        /* Set DMQL Query */
        $query = $this->_get_dmql_query( $query, $mlsKey, 'agent' );

        $data = $this->rets->search( $resource_id, $default_class, $page, $limit, $query );

        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }

        return $this->_prepare_response( array(
          'query' => $query,
          'page' => $page,
          'limit' => $limit,
          'total' => $data[ 'total' ],
          'results' => $data[ 'results' ],
        ) );
      }


      /**
       * Returns Office Results
       *
       * Returns Offices for specified RETS Provider ( mlsKey ).
       *
       * * <code>(IsActive=|0,1)</code> - Status for office..
       *
       * @param string $mlsKey RETS Provider
       * @param int $page Page
       * @param int $limit Limit
       * @param mixed $query DMQL Query
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @internal param string $classID RETS Class
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function office( $mlsKey, $page = 1, $limit = 10, $query = '', $credentials = "production" ) {;

        $config = mpo()->get_mls_config( $mlsKey, "resources" );
        if( !$config['office']['enabled'] ) {
          throw new RestException(400, 'Resource is not enabled for the current MLS' );
        }

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        if( !is_numeric( $page ) || $page < 1 ) {
          $page = 1;
        }
        if( empty( $limit ) || !is_numeric( $limit ) ) {
          $limit = 10;
        }

        /* Get resource ID for Property from our config file. */
        $resource_id = $config['office']['resource'];
        $default_class = $config['office']['class'][0];

        /* Set DMQL Query */
        $query = $this->_get_dmql_query( $query, $mlsKey, 'office' );

        $data = $this->rets->search( $resource_id, $default_class, $page, $limit, $query );

        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }

        return $this->_prepare_response( array(
          'query' => $query,
          'page' => $page,
          'limit' => $limit,
          'total' => $data[ 'total' ],
          'results' => $data[ 'results' ],
        ) );
      }

      /**
       * Returns openHome Results
       *
       * Returns openHome for specified RETS Provider ( mlsKey ).
       *
       * * <code>(IsActive=|0,1)</code> - Status for office..
       *
       * @param string $mlsKey RETS Provider
       * @param string $class Class ( Not required for most MLS providers )
       * @param int $page Page
       * @param int $limit Limit
       * @param mixed $query DMQL Query
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @internal param string $classID RETS Class
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function openHome( $mlsKey, $class = '', $page = 1, $limit = 10, $query = '', $credentials = "production" ) {;

        $config = mpo()->get_mls_config( $mlsKey, "resources" );
        if( !$config['openhome']['enabled'] ) {
          throw new RestException(400, 'Resource is not enabled for the current MLS' );
        }

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        if( !is_numeric( $page ) || $page < 1 ) {
          $page = 1;
        }
        if( empty( $limit ) || !is_numeric( $limit ) ) {
          $limit = 10;
        }

        /* Get resource ID for Property from our config file. */
        $resource_id = $config['openhome']['resource'];
        $class = !empty( $class ) ? $class : $config['openhome']['class'][0];

        /* Set DMQL Query */
        $query = $this->_get_dmql_query( $query, $mlsKey, 'openhome' );

        $data = $this->rets->search( $resource_id, $class, $page, $limit, $query );

        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }

        return $this->_prepare_response( array(
          'query' => $query,
          'page' => $page,
          'limit' => $limit,
          'total' => $data[ 'total' ],
          'results' => $data[ 'results' ],
        ) );
      }

      /**
       * Returns Tours Results
       *
       * Returns openHome for specified RETS Provider ( mlsKey ).
       *
       * * <code>(IsActive=|0,1)</code> - Status for office..
       *
       * @param string $mlsKey RETS Provider
       * @param string $resource Resource ID
       * @param string $class Class
       * @param int $page Page
       * @param int $limit Limit
       * @param mixed $query DMQL Query
       * @param string $credentials Credentials Type ( production,development )
       * @throws RestException
       * @internal param string $classID RETS Class
       * @access protected
       * @class  AccessControl {@requires admin}
       * @return object
       */
      public function tour( $mlsKey, $resource = '', $class = '', $page = 1, $limit = 10, $query = '', $credentials = "production" ) {;

        $config = mpo()->get_mls_config( $mlsKey, "resources" );
        if( !$config['tour']['enabled'] ) {
          throw new RestException(400, 'Resource is not enabled for the current MLS' );
        }

        /* Try to connect to RETS */
        $this->_connect_to_rets( $mlsKey, $credentials );

        if( !is_numeric( $page ) || $page < 1 ) {
          $page = 1;
        }
        if( empty( $limit ) || !is_numeric( $limit ) ) {
          $limit = 10;
        }

        /* Get resource ID for Property from our config file. */
        $resource_id = $config['tour']['resource'];
        $class = !empty( $class ) ? $class : $config['tour']['class'][0];

        /* Set DMQL Query */
        $query = $this->_get_dmql_query( $query, $mlsKey, 'tour' );

        $data = $this->rets->search( $resource_id, $class, $page, $limit, $query );

        if( $this->rets->has_errors() ) {
          throw new RestException(400, $this->rets->get_errors());
        }

        return $this->_prepare_response( array(
          'resource_id' => $resource_id,
          'class' => $class,
          'query' => $query,
          'page' => $page,
          'limit' => $limit,
          'total' => $data[ 'total' ],
          'results' => $data[ 'results' ],
        ) );
      }

      /**
       * Generates DMQL query by passed query argument and defaults defined in config.json
       *
       * @param mixed $query
       * @param mixed $mlsKey
       * @param mixed $defaults
       * @param mixed $resource
       * @return string
       * @throws RestException
       */
      private function _get_dmql_query( $query, $mlsKey, $resource ){
        $defaults = array();
        $defaultQuery = mpo()->get_mls_config( $mlsKey, "resources.{$resource}.defaultDMQLQuery" );

        // Build array from $defaultQuery to merge it with requested query.
        if( !empty($defaultQuery) ) {
          $defaultQuery = explode( '),(', trim( $defaultQuery, '()' ) );
          foreach( $defaultQuery as $condition ) {
            $condition = explode( '=', $condition );
            if( count($condition) == 2 ) {
              $defaults[$condition[0]] = $condition[1];
            }
          }
        }

        /* May be parse query request */
        if( !is_array( $query ) ) {
          $_query = array();
          foreach( explode( '(', trim( $query ) ) as $q ) {
            if(empty($q)) continue;
            $q = explode( '=', trim($q) );
            foreach( $q as $k => $v ) {
              $v = trim( $v );
              $q[$k] = rtrim( $v, '),' );
            }
            $_query[ $q[0] ] = $q[1];
          }
          $query = $_query;
        }
        $query = array_replace( $defaults, $query );
        $dmql = array();

        foreach( $query as $k => $v ) {
          $v = mpo()->parse_query_value( $v );
          array_push( $dmql, "({$k}={$v})" );
        }
        $dmql = implode( ',', $dmql );
        if( empty( $dmql ) ) {
          $dmql = '()';
        }
        return $dmql;
      }

    }

  }

}