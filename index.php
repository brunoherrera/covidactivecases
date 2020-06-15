<!DOCTYPE html>
<html>
<head>
<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: center;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}

h1, h5, h2 {
  text-align: center;
}

h2 {
  color: #8f1f1f;
}
</style>
</head>
<body>



<?php

function getData($date) { // php function to get and convert csv data to json format
  $url = "https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_daily_reports/" . $date . ".csv"; // pull data

  if (!($fp = fopen($url, 'r'))) { // open csv file
    return false;
  }

  $key = fgetcsv($fp,"1024",","); //read csv headers

  $json = array(); // parse csv rows into array
    while ($row = fgetcsv($fp,"1024",",")) {
    $json[] = array_combine($key, $row);
  }

  fclose($fp); // release file handle

  return json_encode($json); // encode array to json
}

$day = 0; // day offset
$res = 0; // initial data state (false, no data)
while (!$res) { // get data with last logged day
  $dataDate =  strval(date('m-d-Y',(strtotime("-$day day"))));
  $res = @getData($dataDate); // raw data container
  $day++;
}
$getData = json_decode($res, true); // decoding JSON string

echo "<h1>Printable COVID-19 Active Cases as of $dataDate</h1>"; // html crap
echo "<h5>Source: <a href=\"https://github.com/CSSEGISandData/COVID-19/tree/master/csse_covid_19_data\" target=\"_blank\">JHU CSSE COVID-19 Dataset</a></h5>"; // html crap
echo "<h2>For some countries/regions, active cases might be larger because there's no data for recovered cases.</h2>"; // html crap
echo "<br>"; // html crap

$withActiveCases = array(); // will contain final filtered data to work with

foreach ($getData as $key => $value) {
  if ($value["Active"] >= 0) { // (fail-safe)
    array_push($withActiveCases, $value);
  }
}

$country = array_column($withActiveCases, 'Country_Region');
array_multisort($country, SORT_ASC, $withActiveCases); // sort by country


$arrayPrev = array(); // will contain previous array to work with in the next processing:
foreach ($withActiveCases as $key => $value) { // GET GENERAL COUNTRY DATA (CITIES MERGED INTO A COUNTRY'S TOTAL COUNT)
  if (isset($arrayPrev["Country_Region"])) { // avoid issues with first comparison
    if ($value["Country_Region"] == $arrayPrev["Country_Region"]) { // if current and previous array are the same country
      $withActiveCases[$key]["Confirmed"] += $arrayPrev["Confirmed"]; // sum their confirmed cases to current array
      $withActiveCases[$key]["Active"] += $arrayPrev["Active"]; // sum their active cases to current array
      unset($withActiveCases[$key - 1]); // discard previous array
    }
  }
  $arrayPrev = $withActiveCases[$key]; // save current array for next comparison
}

$active = array_column($withActiveCases, 'Active');
array_multisort($active, SORT_ASC, $withActiveCases); // sort by active cases (least to greatest)




// HTML TABLE OUTPUT
$row = 1; // row counter
echo "<table>";

echo "<tr>";
echo "<th>N</th>";
echo "<th>Country</th>";
echo "<th>Confirmed</th>";
echo "<th>Active Cases</th>";
echo "</tr>";

foreach ($withActiveCases as $key => $value) {

  echo "<tr>";
  print_r("<td>" . $row++ . "</td>");
  print_r("<td>" . $value["Country_Region"] . "</td>");
  print_r("<td>" . $value["Confirmed"] . "</td>");
  print_r("<td>" . $value["Active"] . "</td>");
  echo "</tr>";

}

echo "</table>";

?>

</body>
</html>