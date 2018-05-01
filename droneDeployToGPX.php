#!/usr/bin/php
<?php

/**
 * @author Douglas Haubert <dhaubert.ti@gmail.com>
 * File created to convert drone deploy csv logs to gpx (GPS Exchange Format)
 */
$hasArguments = $argc > 1;
if( $hasArguments && in_array($argv[1], array('help', '--help', '-h')) ){
      echo getHelp();
} else {
      $directory = $hasArguments && $argv[1] ? trim($argv[1]) : './'  ;
      $outputFile = $argv[2] ?? 'droneDeployLog' . date('Y-m-d-H-i-s') . '.gpx'; 

      $files = getLogFiles($directory);
      if (count($files) <= 0){
            echo "No .txt files were you pointed out ({$directory}).\n";
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

/**
 * Read the directory and get all the log files from there
 * @param string $directory Target dir
 * @return array Array with the log filenames 
 */
function getLogFiles($directory){
      $files = 
            array_filter(
                  scandir($directory), function($filename){
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        return $ext == "txt";
      });
      $files = array_map( function($filename) use ($directory){
            return $directory . $filename;
      }, $files);
      return $files;
}
/**
 * Get the GPX content from all the files
 *
 * @param array $files Array with logs filenames
 * @return string
 */
function getGPX($files){
      foreach( $files as $fileInPath ){
            $fileInPath = $fileInPath;
            $row = 0;
            if (($handle = fopen($fileInPath, "r")) !== FALSE) {
                  while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                        $num = count($data);
                        if ($num > 100){
                        $gpxData[] = [
                                    'datetime' => DateTime::createFromFormat('d/m/Y H:i:s', $data[0]),
                                    'latitude' => $data[17],
                                    'longitude' => $data[18],
                                    'altitude' => number_format($data[20]*0.3048, 3),
                              ];
                              echo "\nlat: {$data[17]} long {$data[18]} alt: ". number_format($data[20]*0.3048, 3);
                        }
                        $row++;
                  }
                  fclose($handle);
            }
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
           ob_start();
           ?>
      <wpt lat="<?= $latitude ?>" lon="<?= $longitude ?>">
            <ele><?= $altitude ?></ele>
            <time><?= $datetime->format('Y-d-m\TH:i:s\Z') //2009-10-17T18:37:26Z  ?></time>
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
