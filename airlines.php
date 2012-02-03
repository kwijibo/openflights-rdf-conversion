<?php
require 'inc.php';

if(!file_exists('airlines.dat')){
  $csv = file_get_contents('http://openflights.svn.sourceforge.net/viewvc/openflights/openflights/data/airlines.dat');
  file_put_contents('airlines.dat', $csv);
}

$fh = fopen('airlines.dat', 'r');

register('dbo','http://dbpedia.org/ontology/');
register('al',NS.'terms/');

$rdf = new RdfBuilder();

$rdf->create_vocabulary('dbo', 'http://dbpedia.org/ontology/', 'dbpedia ontology', 'http://keithalexander.co.uk/id/me');
$rdf->create_vocabulary('al', NS.'terms/', 'Open Flights Airline Vocabulary', 'http://keithalexander.co.uk/id/me');




while ($row = fgetcsv($fh)){
  foreach($row as $k => $v){
    if($v=="\N") $row[$k]='';
  }
  list($id, $name, $alt, $iata, $icao, $callsign, $country, $active) = $row;
  $countryUri = getCountryUri($country);
  $status = (strtolower(trim($active))=='y')? 'active' : 'defunct' ;
  $airline = $rdf->thing(NS.'airline/'.$id);
  $airline->a('dbo:Airline')
    ->label($name, 'en');

  if(!empty($alt)){
      $airline->has('skos:altLabel')->l($alt, 'en');
  }
    if(!empty($iata)){
      $airline->has('dbp:iata')->l($iata);
    }
    if(!empty($icao)){
      $airline->has('dbp:icao')->l($icao);
    }
    if(!empty($callsign)){
      $airline->has('dbp:callsign')->l($callsign);
    }
    if(!empty($countryUri)){
      $airline->has('ov:country')->r($countryUri);
    }
    if(!empty($status)){
      $airline->has('al:status')->l($status, 'en');
    }

  echo $rdf->dump_ntriples();
}

$rdf->write_vocabulary_to_file('al','al_vocab.ttl');

?>
