<?php
require 'inc.php';

$rfh = get_filehandle('routes.dat');

$afh = get_filehandle('airports.dat');

$ourairports = json_decode(file_get_contents('iata.json'),1);

$airports = array();

function getAirportUri($id){
  global $airports;
  global $afh;
  global $ourairports;
  
  if(isset($airports[$id])) return $airports[$id];
  if(!$id) return false;
  while($row = fgetcsv($afh)){
    list($airportId, $name, $city, $country, $iataOrFAA, $icao, $lat, $long, $alt, $tz, $dst) = $row;

    if(trim($airportId) == $id){
      if($icao=='\N' OR empty($icao)){
        @$icao = $ourairports[$iataOrFAA];

        if(!isset($icao)){
          //throw new Exception("no URI found for {$iataOrFAA}");
          $icao = $iataOrFAA;
        }
      }       
      $uri = AIRPORT.$icao;
      $airports[$id] = $uri;
      rewind($afh);
      return $uri;
    }
  }
  throw new Exception("No Airport for code $id");
}

register('dbo','http://dbpedia.org/ontology/');
register('routes',ROUTE.'terms/');
register('transit', 'http://vocab.org/transit/terms/');

$rdf = new RdfBuilder();

$rdf->create_vocabulary('dbo', 'http://dbpedia.org/ontology/', 'dbpedia ontology', 'http://keithalexander.co.uk/id/me');
$rdf->create_vocabulary('routes', ROUTE.'terms/', 'Open Flights Routes Vocabulary', 'http://keithalexander.co.uk/id/me');

$planes = array();
while ($row = fgetcsv($rfh)){
  set_time_limit(0);
  
  foreach($row as $k => $v){
    $row[$k] = trim($v);
    $v = trim($v);
    if($v=="\N") $row[$k]='';
  }
  list($airline, $airlineId, $sourceAirport, $sourceAirportID, $destAirport, $destAirportID, $codeShare, $Stops, $Equipment) = $row;
  $airportA = getAirportUri($sourceAirportID);
  $airportB = getAirportUri($destAirportID);
  $airlineURI = NS.'airline/' . $airlineId; 
  $route = $rdf->thing(ROUTE.'route/'. $airlineId . '-' . $sourceAirportID . '-' . $destAirportID)
    ->a('transit:Route')
    ->label("{$sourceAirport} - {$destAirport} : {$airline}")
    ->has("routes:numberOfStops")->dt($Stops,"xsd:integer");

  if($airportA) $route->has("routes:start")->r($airportA);
  if($airportB) $route->has("routes:end")->r($airportB);
 
  if(strtolower($codeShare)=='y'){
    $route->has("routes:airline")->r($airlineURI);
    $route->a("routes:CodeShareRoute");
  } else {
    $route->has("transit:agency")->r($airlineURI);
  }
  $planeCodes = explode(" ",$Equipment);
  foreach($planeCodes as $plane){
    $planeUri =  ROUTE.'aircraft/'.$plane;     
    $route->has("routes:usualAircraft")->r($planeUri);
    $planes[$plane]=$planeUri;
  }

  echo $rdf->dump_ntriples();
}

foreach($planes as $code => $uri){
  $rdf->thing($uri)->label($code)->a('dbo:Aircraft');
}

echo $rdf->dump_ntriples();


$rdf->write_vocabulary_to_file('routes','routes_vocab.ttl');

fclose($rfh);
?>

