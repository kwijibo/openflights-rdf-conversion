<?php
define('LIB_DIR', '/Users/keithalexander/dev/');
define('MORIARTY_ARC_DIR', LIB_DIR.'arc/');

require LIB_DIR.'moriarty/simplegraph.class.php';
require_once LIB_DIR.'moriarty/httprequestfactory.class.php';
require LIB_DIR.'curieous/rdfbuilder.class.php';

define('NS', 'http://data.kasabi.com/dataset/openflights-airlines/');
define('AIRPORT', 'http://data.kasabi.com/dataset/airports/');
define('ROUTE', 'http://data.kasabi.com/dataset/openflights-routes/');


function get_filehandle($filename){

  if(!file_exists($filename)){
    $csv = file_get_contents('http://openflights.svn.sourceforge.net/viewvc/openflights/openflights/data/'.$filename);
    file_put_contents($filename, $csv);
  }

  $fh = fopen($filename, 'r');
  return $fh;

}

$countries = json_decode(file_get_contents('countries.json'), true);

function getCountryUri($name){
  global $countries;
  foreach($countries['results']['bindings'] as $row){
    if($row['label']['value']==$name){
      return $row['uri']['value'];
    }
  }
}

?>
