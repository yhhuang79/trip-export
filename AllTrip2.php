<?php

    namespace GeoJson;
    use GeoJson\GeoJson;
    use GeoJson\Geometry\LineString;
    use GeoJson\Geometry\Geometry;
	use GeoJson\Geometry\GeometryCollection;
    use GeoJson\Feature\Feature;

    if (!file_exists($autoloadFile = 'vendor/autoload.php')) {
        throw new RuntimeException('Install dependencies to run test suite.');
    }
    require_once $autoloadFile;

	function distance($lat1, $lon1, $lat2, $lon2, $unit) {
		$theta = $lon1 - $lon2;
  		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  		$dist = acos($dist);
  		$dist = rad2deg($dist);
  		$miles = $dist * 60 * 1.1515;
  		$unit = strtoupper($unit);
		if ($unit == "K") {
    		return ($miles * 1.609344);
  		} else if ($unit == "N") {
      		return ($miles * 0.8684);
    	} else {
        	return $miles;
      	}
	}

    $username="postgres";
    $password="root";
    $database="postgistemplate";
    $conn = pg_Connect("host=140.109.18.129 dbname='$database' user='$username' password='$password'");

    if (!$conn){
        echo "Connect error!!!\n";
        exit;
    }

    $queryStr = "SELECT token FROM (SELECT DISTINCT ON (token) token, timestamp FROM antrip_data.realtime_sharing_points ORDER BY token, timestamp DESC) AS t ORDER BY t.timestamp DESC;";
    $result = pg_query($conn, $queryStr);
    if (!$result) {
        echo "Query error occurred.\n";
        exit;
    }

    $trajectories = array();
    while ($row = pg_fetch_row($result)) {
		$queryStr = "SELECT latitude, longitude FROM antrip_data.realtime_sharing_points WHERE todisplay = TRUE AND token = '".$row[0]."' ORDER BY timestamp ASC";
		$coords = pg_query($conn, $queryStr);
		if (!$coords) {
        	echo "Query error occurred.\n";
        	exit;
    	}
		$coordinates = array();
		$distances = array();
		$velocity = array();
		$misspoint = 1;
		while ($coord = pg_fetch_row($coords)) {
			if ((!is_null($coord[0])) && (!is_null($coord[1]))) {
            	if ($coord[1] > 1000000)
                	$latlon = array($coord[1]/1000000, $coord[0]/1000000);
                else
               		$latlon = array($coord[1]*1, $coord[0]*1);
                if (($latlon[0] < 180) && ($latlon[1] < 90)) {
					if (count($coordinates) == 0) {
						//echo "empty coordinate";
	                    array_push($coordinates, $latlon);
					} else {
						$dist = distance($coordinates[count($coordinates)-1][1],$coordinates[count($coordinates)-1][0],$latlon[1],$latlon[0],"K");
						if (count($distances) == 0) {
							//echo "empty distance";
							array_push($distances, $dist);
							array_push($velocity, $dist/10);
						} else {
							if ((($dist/10) < ($velocity[count($velocity)-1]*9)) && (($dist/10) < 100)) {
								array_push($coordinates, $latlon);
								array_push($distances, $dist);
								array_push($velocity, $dist/(10*$misspoint));
								$misspoint = 1;
							} else {
								$misspoint++;
							}
						}
					}
                }
            }
		}
		if (count($coordinates) > 2) {
            $trajectory = new LineString($coordinates);
            array_push($trajectories, $trajectory);
        }
	}
    $allTrip = new GeometryCollection($trajectories);
    echo "var alltrack = " . json_encode($allTrip) . ";";
?>
