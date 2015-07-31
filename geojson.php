<?php
    namespace GeoJson\Tests\Geometry;
    use GeoJson\GeoJson;
    use GeoJson\Geometry\LineString;
    use GeoJson\Tests\BaseGeoJsonTest;

	if (!file_exists($autoloadFile = 'vendor/autoload.php')) {
    	throw new RuntimeException('Install dependencies to run test suite.');
	}
	require_once $autoloadFile;

	$field_mask = "0100000000000000";
	$url = "https://plash.iis.sinica.edu.tw:8080/GetCheckInData?userid=139&trip_id=679&field_mask=" . $field_mask; 
	$ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); // Set so curl_exec returns the result instead of outputting it. 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$response = curl_exec($ch);
	curl_close($ch);

	$obj = json_decode($response);
	$coord = $obj->{'CheckInDataList'};

	$coordinates = array();
	foreach ($coord as $c) {
		echo $c->{'lng'} .", " . $c->{'lat'} . "\n";
		$latlon = array($c->{'lng'}/1000000, $c->{'lat'}/1000000);
		array_push($coordinates, $latlon);
	}
	$lineString = new LineString($coordinates);
	$json = json_encode($lineString);
	echo $json;
?>
