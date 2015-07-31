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

    $username="postgres";
    $password="root";
    $database="postgistemplate";
    $conn = pg_Connect("host=140.109.18.129 dbname='$database' user='$username' password='$password'");

    if (!$conn){
        echo "Connect error!!!\n";
        exit;
    }

    $queryStr = "SELECT user_location.trip_info.id,user_location.trip_info.userid, user_location.trip_info.trip_id FROM user_location.trip_info ORDER BY user_location.trip_info.id ASC LIMIT 19000";
    $result = pg_query($conn, $queryStr);
    if (!$result) {
        echo "Query error occurred.\n";
        exit;
    }

    $trajectories = array();
    while ($row = pg_fetch_row($result)) {
        $field_mask = "0100000000000000";
        $url = "https://plash.iis.sinica.edu.tw:8080/GetCheckInData?userid=". $row[1] ."&trip_id=". $row[2] ."&field_mask=" . $field_mask; 
        $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); // Set so curl_exec returns the result instead of outputting it. 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $obj = json_decode($response);
		if (isset($obj->{'CheckInDataList'})) {
			$checkins = $obj->{'CheckInDataList'};
			if	(count($checkins) > 2) {
	        	$coordinates = array();
    		    foreach ($checkins as $c) {
        			//echo $c->{'lng'} .", " . $c->{'lat'} . "\n";
					if ($c->{'lng'} != 0) {
						if ($c->{'lng'}>1000000)
        					$latlon = array($c->{'lng'}/1000000, $c->{'lat'}/1000000);
						else
							$latlon = array($c->{'lng'}, $c->{'lat'});
						if (($latlon[0] < 180) && ($latlon[1] < 90)){
							//echo $latlon[0].", ".$latlon[1]."\n";
							array_push($coordinates, $latlon);
						}
					}
        		}
				if (count($coordinates) > 2) {
        			$trajectory = new LineString($coordinates);
        			array_push($trajectories, $trajectory);
				}
				//echo "-------------------\n";
			}
        //$json = json_encode($lineString);
        //echo $json;
    	}
	}
    $allTrip = new GeometryCollection($trajectories);
    echo json_encode($allTrip);
?>
