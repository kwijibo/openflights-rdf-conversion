<?php
require 'inc.php';

$afh = get_filehandle('airports.dat');
$ourairports = json_decode(file_get_contents('iata.json'),1);

/* Airports */
$rdf = new RdfBuilder();
$rdf->create_vocabulary('dbo', 'http://dbpedia.org/ontology/', 'dbpedia ontology', 'http://keithalexander.co.uk/id/me');
$rdf->create_vocabulary('airport', AIRPORT.'terms/', 'Airport Vocabulary', 'http://keithalexander.co.uk/id/me');

while($row = fgetcsv($afh)){
    foreach($row as $k => $v){
      $row[$k] = trim($v);
      $v = trim($v);
      if($v=='\N') $row[$k]='';
  }
  list($airportId, $name, $city, $country, $iataOrFAA, $icao, $lat, $long, $alt, $tz, $dst) = $row;
  $metricAlt = $alt * 0.3048;

  $countryUri = getCountryUri($country);
  $countryCode = array_pop(explode('/', $countryUri));
  $placeUri = $rdf->thing(AIRPORT.'place/'. $countryCode . '/' . $city)
    ->has('spatial:PP')->r($countryUri)->a('places:City')->label($city, 'en')->get_uri();

  if(!empty($icao)){
    $key = $icao;
  } else if(!empty($iataOrFAA) AND !empty($ourairports[$iataOrFAA])){
    $key = $ourairports[$iataOrFAA];
  } else if(!empty($iataOrFAA)){
    $key = $iataOrFAA;
  } else {
    $key = 'openflights'.$airportId;
  }
  $airport = $rdf->thing_from_identifier(AIRPORT, $key)
      ->a('dbo:Airport')
      ->label($name, 'en')
      ->has("foaf:based_near")->r($placeUri)
      ->has("dbp:iata")->l($iataOrFAA)
      ->has("dbp:icao")->l($icao)
      ->has("geo:lat")->dt($lat,'xsd:float')
      ->has("geo:long")->dt($long, 'xsd:float')
      ->is("routes:airport")->of($placeUri);

  if($alt)  $airport->has("geo:alt")->dt($alt, 0, 'xsd:decimal');

  echo $rdf->dump_ntriples();
}



?>
