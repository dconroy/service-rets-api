<?php

namespace MPO {

  use PHRETS\Models\Search\Results;

  /**
   * Class Parser extended from OneX.
   * Need to alter parser method to process response before parsing it as XML.
   *
   * @package MPO
   */
  class Parser extends \PHRETS\Parsers\Search\OneX {

    /**
     * A bit custom xml creator implementation
     * We just convert raw response from ISO-8859-1 to UTF-8.
     * Standard SimpleXMLElement() could not parse weired characters.
     *
     * @param $response
     * @return \SimpleXMLElement
     */
    private function xml( $response ) {
      $data = (string)$response->getBody();

      $disableEntities = libxml_disable_entity_loader(true);
      $internalErrors = libxml_use_internal_errors(true);

      $xml = new \SimpleXMLElement(iconv("ISO-8859-1", "UTF-8", $data) ?: '<root />', LIBXML_NONET, false, '', false);
      libxml_disable_entity_loader($disableEntities);
      libxml_use_internal_errors($internalErrors);

      return $xml;
    }

    /**
     * A bit altered parse method that utilizes our custom xml method.
     *
     * @param \PHRETS\Session $rets
     * @param \GuzzleHttp\Message\ResponseInterface $response
     * @param $parameters
     * @return Results
     */
    public function parse(\PHRETS\Session $rets, \GuzzleHttp\Message\ResponseInterface $response, $parameters) {

      /**
       * Only this line was changed in this method
       */
      $xml = $this->xml( $response );

      $rs = new Results;
      $rs->setSession($rets)
        ->setResource($parameters['SearchType'])
        ->setClass($parameters['Class']);

      if ($this->getRestrictedIndicator($rets, $xml, $parameters)) {
        $rs->setRestrictedIndicator($this->getRestrictedIndicator($rets, $xml, $parameters));
      }

      $rs->setHeaders($this->getColumnNames($rets, $xml, $parameters));
      $rets->debug(count($rs->getHeaders()) . ' column headers/fields given');

      $this->parseRecords($rets, $xml, $parameters, $rs);

      if ($this->getTotalCount($rets, $xml, $parameters) !== null) {
        $rs->setTotalResultsCount($this->getTotalCount($rets, $xml, $parameters));
        $rets->debug($rs->getTotalResultsCount() . ' total results found');
      }
      $rets->debug($rs->getReturnedResultsCount() . ' results given');

      if ($this->foundMaxRows($rets, $xml, $parameters)) {
        $rs->setMaxRowsReached();
        $rets->debug('Maximum rows returned in response');
      }

      unset($xml);

      return $rs;
    }

  }

}