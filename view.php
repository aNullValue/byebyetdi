<?php
require_once("functions.php");

if (!isset($_GET['car_id']) or !isset($_GET['region_id']) or !isset($_GET['miles']) or !isset($_GET['month_offset'])) {
    die('required parameter missing.');
}

if (!is_numeric($_GET['car_id']) or !is_numeric($_GET['region_id']) or !is_numeric($_GET['miles']) or !is_numeric($_GET['month_offset'])) {
    die('required parameter inappropriately set.');
}

if ($_GET['miles'] < 1) {
    die('you must provide a quantity of miles');
}

$mileage_reduction = mileagemonths_list();
if (!isset($mileage_reduction[($_GET['month_offset'])])) {
    die('invalid month offset');
}

$v1 = getcarinfo($_GET['car_id'], $_GET['region_id'], $_GET['miles'], $_GET['month_offset']);

echo "<html><head><title>Unofficial VW Settlement Calculator</title></head><body>";

echo_googleanalyticsscript('redacted');

echo "<br /><br />THIS CALCULATOR IS UNOFFICIAL, AND IS NOT ENDORSED BY VOLKSWAGEN, VOLKSWAGEN OF AMERICA, OR ANY GOVERNMENT AGENCY.<br /><br />";
echo "DO NOT RELY UPON THIS CALCULATOR FOR ANY REASON WHATSOEVER.<br /><br />";

foreach ($v1 as $car) {
    echo $car['Year'] . ' ' . $car['Model'] . ' ' . $car['BodyStyle'] . ', as sold in NADA region ' . $car['Region'] . '.<br /><br /><br />';
    
    $thiscar_buyback = $car['Buyback'];
    $thiscar_modification = $car['Modification'];
    
    echo "<table border=1>";
    
    echo "<tr>";
    echo "<th>Item</th>";
    echo "<th>Buyback Amount</th>";
    echo "<th>Modification Amount</th>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td>" . $car['Year'] . ' ' . $car['Model'] . ' ' . $car['BodyStyle'] . "</td>";
    echo "<td>" . money_format('%n', $car['Buyback']) . "</td>";
    echo "<td>" . money_format('%n', $car['Modification']) . "</td>";
    echo "</tr>";
    
    foreach ($car['options'] as $opt) {
        if (isset($_GET['options'][($opt['ov_rowid'])])) {
            echo "<tr>";
            echo "<td>Option: " . $opt['Option'] . "</td>";
            echo "<td>" . money_format('%n', $opt['Buyback']) . "</td>";
            echo "<td>" . money_format('%n', $opt['Modification']) . "</td>";
            echo "</tr>";
        
            $thiscar_buyback += $opt['Buyback'];
            $thiscar_modification += $opt['Modification'];
        }
    }
    
    foreach ($car['mileage'] as $miles) {
        echo "<tr>";
        
        echo "<td>";
        
        echo "Miles: " . $miles['Lower'] . " - " . $miles['Upper'] . "<br />";
        echo "Odometer miles: " . $_GET['miles'] . "<br />";
        echo "Calculation month: " . $mileage_reduction[($_GET['month_offset'])]['name'] . "<br />";
        echo "Mileage Adjustment: " . $mileage_reduction[($_GET['month_offset'])]['miles'] . "<br />";
        echo "Effective miles: " . ($_GET['miles'] + $mileage_reduction[($_GET['month_offset'])]['miles']) . "<br />";
        echo "</td>";
        
        echo "<td>" . money_format('%n', $miles['Buyback']) . "</td>";
        echo "<td>" . money_format('%n', $miles['Modification']) . "</td>";
        echo "</tr>";
        
        $thiscar_buyback += $miles['Buyback'];
        $thiscar_modification += $miles['Modification'];
    
    }
    
    if ($thiscar_modification < 5100) {
        $further_adjust = 5100 - $thiscar_modification;
        $thiscar_buyback += $further_adjust;
        $thiscar_modification += $further_adjust;
        echo "<tr>";
        echo "<td>Further Adjustment</td>";
        echo "<td>" . money_format('%n', $further_adjust) . "</td>";
        echo "<td>" . money_format('%n', $further_adjust) . "</td>";
        echo "</tr>";
    }
    else {
        echo "<tr>";
        echo "<td>Further Adjustment</td>";
        echo "<td>$0</td>";
        echo "<td>$0</td>";
        echo "</tr>";
    }
    
    echo "<tr>";
    echo "<td>(Total)</td>";
    echo "<td>" . money_format('%n', $thiscar_buyback) . "</td>";
    echo "<td>" . money_format('%n', $thiscar_modification) . "</td>";
    echo "</tr>";
    
    echo "</table>";

    echo "<br /><br />";

}

echo "</body></html>";

?>