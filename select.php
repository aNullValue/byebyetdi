<?php
require_once("functions.php");

$mileage_reduction = mileagemonths_list();
$territories = territories_list();
$cars = cars_list();

if (
isset($_GET['car_id']) and isset($_GET['region_id']) and isset($_GET['miles']) and isset($_GET['month_offset'])
and
is_numeric($_GET['car_id']) and is_numeric($_GET['region_id']) and is_numeric($_GET['miles']) and is_numeric($_GET['month_offset'])
)
{
    $v1 = getcarinfo($_GET['car_id'], $_GET['region_id'], $_GET['miles'], $_GET['month_offset']);
    foreach ($v1 as $car) {
        if (count($car['options']) < 1) {
            header("Location: view.php?car_id=" . $_GET['car_id'] . "&region_id=" . $_GET['region_id'] . "&miles=" . $_GET['miles'] . "&month_offset=" . $_GET['month_offset']);
            die();
        }
    }
}

echo "<html><head><title>Unofficial VW Settlement Calculator</title></head><body>";

echo_googleanalyticsscript('redacted');

echo "<br /><br />THIS CALCULATOR IS UNOFFICIAL, AND IS NOT ENDORSED BY VOLKSWAGEN, VOLKSWAGEN OF AMERICA, OR ANY GOVERNMENT AGENCY.<br /><br />";
echo "DO NOT RELY UPON THIS CALCULATOR FOR ANY REASON WHATSOEVER.<br /><br />";

if (isset($v1)) {
    echo '<form method="get" action="view.php">';
}
else {
    echo '<form method="get" action="select.php">';
}
echo "In which US State or Territory was the vehicle sold?<br />";

echo '<select name="region_id">';
foreach ($territories as $state) {
    echo '<option value="' . $state['region_id'] . '" ';
    if (isset($_GET['region_id']) and is_numeric($_GET['region_id']) and ($_GET['region_id'] == $state['region_id'])) {
        echo ' selected ';
    }
    echo ' >' . $state['State'] . '</option>';
}
echo '</select>';

echo "<br /><br />";

echo "Which year/model/trim?<br />";
echo '<select name="car_id">';
foreach ($cars as $car) {
    echo '<option value="' . $car['car_id'] . '" ';
    if (isset($_GET['car_id']) and is_numeric($_GET['car_id']) and ($_GET['car_id'] == $car['car_id'])) {
        echo ' selected ';
    }
    echo '>' . $car['Year'] . ' ' . $car['Model'] . ' ' . $car['BodyStyle'] . '</option>';
}
echo '</select>';

echo "<br /><br />";

echo "What is the odometer reading for the desired month?<br />";
echo '<input name="miles" value="';
if (isset($_GET['miles']) and is_numeric($_GET['miles'])) {
    echo $_GET['miles'];
}
else {
    echo 0;
}
echo '" />';

echo "<br /><br />";

echo "For which month would you like to calculate the result?<br />";
echo '<select name="month_offset">';
foreach ($mileage_reduction as $month) {
    echo '<option value="' . $month['month_num'] . '" ';
    if (isset($_GET['month_offset']) and is_numeric($_GET['month_offset']) and ($_GET['month_offset'] == $month['month_num'])) {
        echo ' selected ';
    }
    echo '>' . $month['name'] . '</option>';
}
echo '</select>';

echo "<br /><br />";

if (isset($v1)) {
    foreach ($v1 as $car) {
        if (count($car['options']) > 0) {
            echo 'Please select all options that apply:<br />';        
            foreach ($car['options'] as $opt) {
                echo '<input type="checkbox" name="options[' . $opt['ov_rowid'] . ']" >' . $opt['Option'] . "<br /><br />";
            }
        }
    }
    echo "<br /><br />";
}

echo '<input type="submit" />';

echo "</form>";

echo "</body></html>";

?>