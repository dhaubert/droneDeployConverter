#!/usr/bin/php
<?php

/**
 * @author Douglas Haubert <dhaubert.ti@gmail.com>
 * File created to generate gpx (GPS Exchange Format) file from images
 */
$hasArguments = $argc > 1;
if( $hasArguments && in_array($argv[1], array('help', '--help', '-h')) ){
      echo getHelp();
} else {
      $directory = $hasArguments && $argv[1] ? trim($argv[1]) : './'  ;
      $outputFile = $argv[2] ?? 'gpxFromImages-' . date('Y-m-d-H-i-s') . '.gpx'; 

      //$files = getCoordinatesFromImages($directory);
      $files = getImageFiles($directory);
      print_r($files);
      if (count($files) <= 0){
            echo "No JPG files were you pointed out ({$directory}).\n";
      }
      $gpxFormat = getGPX($files);
      $message = writeFile($outputFile, $gpxFormat);
      echo $message;
}


/**
 * Help message
 * @return string
 */
function getHelp(){
      $help = "-- DroneDeploy Logs to GPX converter --\n";
      $help .= "-- Options: \n";
      $help .= "> droneDeploy2Gpx <directory> <outputFile> \n";
      $help .= "# where <directory> stands for the directory with droneDeploy log files (.txt). Optionally gets the current dir.\n";
      $help .= "# and <outputFile> stands for the result of the GPX file. Optionally writes to droneDeployLog<date>.gpx\n";
      $help .= "Example: droneDeploy2Gpx ./myDroneDeployLogs/ myGPX.gpx\n";      
      return $help;
}
function getCoordinatesFromImage($imageFile){
    $coordinates = shell_exec("exiftool -c \"%.8f\" -datetimeoriginal -gpslatitude -gpslongitude -gpsaltitude -T -r {$imageFile}");
    return $coordinates;
}
/**
 * Read the directory and get all the log files from there
 * @param string $directory Target dir
 * @return array Array with the log filenames 
 */
function getImageFiles($directory){
      $files = 
            array_filter(
                  scandir($directory), function($filename){
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        return $ext == "JPG";
      });
      $files = array_map( function($filename) use ($directory){
            return $directory . $filename;
      }, $files);

      asort($files);
      return $files;
}
/**
 * Get the GPX content from all the files
 *
 * @param array $files Array with logs filenames
 * @return string
 */
function getGPX($files){
      //$files = explode("\n", $files);
      
      foreach( $files as $imageFile ){
            $coordinateRow = getCoordinatesFromImage($imageFile);
            $row = 0;
            //echo "$coordinateRow\n";
            $data = explode("\t", $coordinateRow);
            $num = count($data);
            if ( $num > 0 && !empty($data[1]) ){
            $gpxData[] = [
                        'datetime' => DateTime::createFromFormat('Y:m:d H:i:s', $data[0]),
                        'latitude' => '-' . ((float) $data[1]),
                        'longitude' => '-' . ((float) $data[2]),
                        'altitude' => number_format(((float) $data[3] - 513), 3),
                    ];
            print_r($gpxData);
            }
            $row++;
      }
      $output = gpxFormat($gpxData);
      return $output;
}

/**
 * Write the output file 
 *
 * @param string $outputFile Name of the file or the complete path
 * @param string $content Content of the file
 * @return string Message with the status of the writing
 */
function writeFile($outputFile, $content){
     $written = file_put_contents($outputFile, $content);
     return $written? "File successfully written to {$outputFile}\n": "File couldn't be created\n";
}

/**
 * It formats a row with GPX xml coordinate format
 *
 * @param [DateTime] $datetime Date of the 
 * @param [float] $latitude Decimal Latitude
 * @param [float] $longitude Decimal Longitude
 * @param [float] $altitude Altitude in meters
 * @return string 
 */
function gpxFormatRow($datetime, $latitude, $longitude, $altitude) {
   if($datetime){
        ob_start();?>
      <wpt lat="<?= $latitude ?>" lon="<?= $longitude ?>">
            <ele><?= $altitude ?></ele>
            <time><?= $datetime->format('m/d/Y\TH:i:s\Z') //2009-10-17T18:37:26Z  ?></time>
      </wpt>
<?php
   $element = ob_get_clean();
   }
   return $element;
}
/**
 * Get all the rows readed from drone deploy logs and convert to gpx format
 *
 * @param array $rows Each drone deploy row f
 * @return string String with GPX content
 */
function gpxFormat($rows) {
      ob_start();
      ?>
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<gpx
xmlns="http://www.topografix.com/GPX/1/1"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd"
version="1.1" 
creator="Douglas Haubert">
      <?php
      foreach($rows as $row){
            echo gpxFormatRow($row['datetime'], $row['latitude'], $row['longitude'], $row['altitude']);
      }
      ?>
</gpx><?php
      return ob_get_clean();
   }
