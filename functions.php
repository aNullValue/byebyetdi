<?php

setlocale(LC_MONETARY, 'en_US');

$db = new SQLite3('./db.sqlite');

function echo_googleanalyticsscript($tracker) {
    echo "
    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

      ga('create', '" . $tracker . "', 'auto');
      ga('send', 'pageview');

    </script>
    ";
}

function mileagemonths_list() {
    $outarr = array();
    $outarr[13] = array( 'name' => 'October 2016' );
    $outarr[14] = array( 'name' => 'November 2016' );
    $outarr[15] = array( 'name' => 'December 2016' );
    $outarr[16] = array( 'name' => 'January 2017' );
    $outarr[17] = array( 'name' => 'February 2017' );
    $outarr[18] = array( 'name' => 'March 2017' );
    $outarr[19] = array( 'name' => 'April 2017' );
    $outarr[20] = array( 'name' => 'May 2017' );
    $outarr[21] = array( 'name' => 'June 2017' );
    $outarr[22] = array( 'name' => 'July 2017' );
    $outarr[23] = array( 'name' => 'August 2017' );
    $outarr[24] = array( 'name' => 'September 2017' );
    $outarr[25] = array( 'name' => 'October 2017' );
    $outarr[26] = array( 'name' => 'November 2017' );
    $outarr[27] = array( 'name' => 'December 2017' );
    foreach ($outarr as $offset => $detail) {
        $outarr[($offset)]['month_num'] = $offset;
        $outarr[($offset)]['miles'] = -1042*$offset;
    }
    return $outarr;
    
}

function getcarinfo($carid, $regionid, $miles = 0, $month_offset = 13) {
    global $db;
    $mileage_reduction = mileagemonths_list();
    
    if (!isset($mileage_reduction[($month_offset)])) {
        die('inappropriate month offset');
    }
    else {
        if ($miles > 0) {
            $miles = $miles + $mileage_reduction[($month_offset)]['miles'];
            if ($miles < 0) {
                $miles = 1;
            }
        }
    }
    
    if (!is_numeric($carid) or !is_numeric($regionid) or !is_numeric($miles) or !is_numeric($month_offset)) {
        die('invalid request');
    }
    $outarr = array();
    $car_query = "
        SELECT cv.Year, cv.Model, cv.BodyStyle, cv.Region, cv.Buyback, cv.Modification, cv.ROWID AS cvr_rowid, nr.ROWID as region_rowid, cars.ROWID as car_rowid
        FROM car_value_byregion cv
        INNER JOIN cars ON cv.Year = cars.Year AND cv.Model = cars.Model AND cv.BodyStyle = cars.BodyStyle AND cars.ROWID= " . $carid . "
        INNER JOIN nada_regions nr ON cv.Region = nr.Region AND nr.ROWID = " . $regionid . "
    ";
    $car_results = $db->query($car_query);
    while ($car_row = $car_results->fetchArray()) {
        $this_car = $car_row;
        $this_car['options'] = array();
        $this_car['mileage'] = array();
        
        $options_query = "
            SELECT ov.Year, ov.Model, ov.BodyStyle, ov.Region, ov.Option, ov.Buyback, ov.Modification, ov.ROWID AS ov_rowid
            FROM option_value_byregion ov
            INNER JOIN cars ON ov.Year = cars.Year AND ov.Model = cars.Model AND (ov.BodyStyle = cars.BodyStyle or ov.BodyStyle = 'All') AND cars.ROWID= " . $this_car['car_rowid'] . "
            INNER JOIN nada_regions nr ON ov.Region = nr.Region AND nr.ROWID = " . $this_car['region_rowid'] . "
            ";
        $options_results = $db->query($options_query);
        while ($options_row = $options_results->fetchArray()) {
            $this_car['options'][] = $options_row;
        }
        
        $miles_query = "
            SELECT m.Lower, m.Upper, m.Buyback, m.Modification, m.ROWID as m_rowid
            FROM mileage_adjustment m
            INNER JOIN cars ON m.Year = cars.Year AND m.Model = cars.Model AND cars.ROWID= " . $this_car['car_rowid'] . "
            ";
        if ($miles > 0) {
            
            $miles_query .= "
            WHERE m.Lower <= " . $miles . "
            AND m.Upper >= " . $miles . "
            ";
        }
        $miles_query .= "
            ORDER BY m.Lower
        ";
        
        $miles_results = $db->query($miles_query);
        while ($miles_row = $miles_results->fetchArray()) {
            $this_car['mileage'][] = $miles_row;
        }
        
        $outarr[] = $this_car;
        unset($this_car);
    }
    
    unset($car_row);
    unset($car_results);
    
    return $outarr;
    
}

function territories_list() {
    global $db;
    $outarr = array();
    $results = $db->query('SELECT *, ROWID as region_id FROM nada_regions ORDER BY State');
    while ($row = $results->fetchArray()) {
        $outarr[] = $row;
    }
    unset($row);
    unset($results);
    return $outarr;
}

function regions_list() {
    global $db;
    $outarr = array();
    $results = $db->query('SELECT DISTINCT Region FROM nada_regions');
    while ($row = $results->fetchArray()) {
        $outarr[] = $row['Region'];
    }
    unset($row);
    unset($results);
    return $outarr;
}

function modelyears_list() {
    global $db;
    $outarr = array();
    $results = $db->query('SELECT DISTINCT Year FROM cars');
    while ($row = $results->fetchArray()) {
        $outarr[] = $row['Year'];
    }
    unset($row);
    unset($results);
    return $outarr;
}

function models_listbyyear($year) {
    global $db;
    $outarr = array();
    $results = $db->query("SELECT DISTINCT Model FROM cars WHERE Year = '" . $year . "'");
    while ($row = $results->fetchArray()) {
        $outarr[] = $row['Model'];
    }
    unset($row);
    unset($results);
    return $outarr;
}

function bodystyle_list($year,$model) {
    global $db;
    $outarr = array();
    $query = "
        SELECT DISTINCT BodyStyle
        FROM cars
        WHERE Year = '" . $year . "'
        AND Model = '" . $model . "'
    ";
    $results = $db->query($query);
    while ($row = $results->fetchArray()) {
        $outarr[] = $row['BodyStyle'];
    }
    unset($row);
    unset($results);
    return $outarr;
}

function cars_list() {
    global $db;
    $outarr = array();
    $query = "
        SELECT *, ROWID AS car_id
        FROM cars
        ORDER BY Year, Model, BodyStyle
    ";
    $results = $db->query($query);
    while ($row = $results->fetchArray()) {
        $outarr[] = $row;
    }
    unset($row);
    unset($results);
    return $outarr;
}

function car_region_get($carid, $regionid) {
    global $db;
    
}

function car_region_value($year, $model, $bodystyle, $region) {
    global $db;
    $query = "
        SELECT *
        FROM car_value_byregion
        WHERE Year = '" . $year . "'
        AND Model = '" . $model . "'
        AND BodyStyle = '" . $bodystyle . "'
        AND Region = '" . $region . "'
    ";
    $results = $db->query($query);
    $row = $results->fetchArray();
    return $row;
}

function option_region_value($year, $model, $bodystyle, $region) {
    global $db;
    $outarr = array();
    $query = "
        SELECT *
        FROM option_value_byregion
        WHERE Year = '" . $year . "'
        AND Model = '" . $model . "'
        AND BodyStyle = '" . $bodystyle . "'
        AND (Region = '" . $region . "' OR Region = 'All')
    ";
    $results = $db->query($query);
    while ($row = $results->fetchArray()) {
        $outarr[] = $row;
    }
    unset($row);
    unset($results);
    return $outarr;
}

function mileage_adjust_get($year,$model,$miles) {
    global $db;
    $query = "
        SELECT *
        FROM mileage_adjustment
        WHERE Year = '" . $year . "'
        AND Model = '" . $model . "'
        AND Lower <= " . $miles . "
        AND Upper >= " . $miles . "
    ";
    $results = $db->query($query);
    $row = $results->fetchArray();
    return $row;
}

?>