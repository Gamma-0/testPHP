<?php

include 'Composer/iCal-parser/src/EventObject.php';
include 'Composer/iCal-parser/src/ICal.php';
use ICal\ICal;

class Hour {
  public $hour;
  public $minute;

  /**
  * Return True if the hour is well formed, else, return False
  * (hour between 0 and 23 and minute between 0 and 59)
  * @return bool
  */
  public function is_correct() {
    if ($this->hour >= 0 && $this->hour <= 23 && $this->minute >= 0 && $this->minute <= 59){
      return True;
    }
    return False;
  }

  /**
  * Return True if the two hours are equals, else, return False
  * @param Hour $hour
  * @return bool
  */
  public function is_equal($hour) {
    return ($this->hour == $hour->hour && $this->minute == $hour->minute);
  }

  /**
  * Return True if the hour given in parameters is higher than this, else, return False
  * @param Hour $hour
  * @return bool
  */
  public function is_sup($hour) {
    return ($this->hour < $hour->hour || ($this->hour == $hour->hour && $this->minute < $hour->minute));
  }

  /**
  * this take the value h1-h2
  * @param Hour $h1
  * @param Hour $h2
  */
  public function substract($h1, $h2) {
    $this->hour = $h1->hour - $h2->hour;
    $this->minute = $h1->minute - $h2->minute;

    if ($this->minute < 0){
      $this->hour--;
      $this->minute+= 60;
    }
    if ($this->hour < 0){
      $this->hour+=24;
    }
  }

  /**
  * this take the value h1+h2
  * @param Hour $h1
  * @param Hour $h2
  */
  public function add($h1, $h2) {
    $this->hour = $h1->hour + $h2->hour;
    $this->minute = $h1->minute + $h2->minute;

    if ($this->minute >= 60){
      $this->hour++;
      $this->minute-= 60;
    } // Can't check if hour > 23 to keep a coherent values. Have to check after if we want a correct hour and not a delay.
  }

  /**
  * Convert a string to an hour
  * @param string $hour
  */
  public function str_to_hour($hour) {
    $hour = explode(":", $hour);
    if (count($hour)!=2){
      exit("The hour is malformed.\nFormat: 'HH:MM'");
    }
    $this->hour = $hour[0];
    $this->minute = $hour[1];
    if (!$this->is_correct()){
      exit("The hour is malformed.\n0 <= hour <= 23, 0 <= minute <= 59");
    }
  }

  public function __construct ($hour = 0, $minute = 0){
    $this->hour = $hour;
    $this->minute = $minute;
  }
}



class Interval {
  public $begin; //new Hour();
  public $end; //new Hour();

  /**
  * Return True if the hour given in parameters is in the interval, else, return False
  * @param Hour $hour
  * @return bool
  */
  public function is_in_interval($hour) {
    if (($hour->hour == $this->begin->hour && $hour->minute >= $this->begin->minute) || $hour->hour > $this->begin->hour){
      if (($hour->hour == $this->end->hour && $hour->minute <= $this->end->minute) || $hour->hour < $this->end->hour){
        return True;
      }
    }
    return False;
  }

  /**
  * Return True if the hour given in parameters is before the interval, else, return False
  * @param Hour $hour
  * @return bool
  */
  public function is_before_interval($hour) {
    return (($hour->hour == $this->begin->hour && $hour->minute < $this->begin->minute) || $hour->hour < $this->begin->hour);
  }

  /**
  * Return True if the hour given in parameters is after the interval, else, return False
  * @param Hour $hour
  * @return bool
  */
  public function is_after_interval($hour) {
    return (($hour->hour == $this->end->hour && $hour->minute > $this->end->minute) || $hour->hour > $this->end->hour);
  }

  /**
  * Return the effective time left before the hour reach the end of the interval
  * @param Hour $hour
  * @return Hour
  */
  public function time_left($hour) {
    $tl = new Hour();
    if ($this->is_before_interval($hour)){
      $tl->substract($this->end, $this->begin); // $tl = $this->end - $this->begin;
    } else if ($this->is_in_interval($hour)){
      $tl->substract($this->end, $hour); // $tl = $this->end - $hour;
    } // else { return $tl; }
    return $tl;
  }

  public function __construct ($begin, $end){
    $this->begin = $begin;
    $this->end = $end;
  }

  /*
  public function __construct ($begin_hour, $begin_minute, $end_hour, $end_minute){
    $this->begin = new Hour($begin_hour, $begin_minute);
    $this->end = new Hour($end_hour, $end_minute);
  }
  */
}



class Schedule {
  public $week;

  /**
  * Add a new interval to a given day. Exit if there is an error.
  * @param string $day
  * @param string $hour_begin
  * @param string $hour_end
  */
  public function add_hour($day, $hour_begin, $hour_end) {
    $begin = new Hour();
    $begin->str_to_hour($hour_begin);

    $end = new Hour();
    $end->str_to_hour($hour_end);

    $size = count($this->week[$day]);
    $this->week[$day][$size] = new Interval($begin, $end);
  }

  /**
  * Check if the delay is elapsed on a given day
  * If the delay is elapsed, return True and the hour
  * Else, return False and the time left
  * @param string $day
  * @param Hour $hour
  * @param Hour $delay
  * @return [bool, Hour]
  */
  public function check_calendar($day, $hour, $delay) {
    $size = count($this->week[$day]);
    $tl = new Hour();
    for ($i = 0; $i < $size; $i++){
      $tl = $this->week[$day][$i]->time_left($hour);
      if ($delay->is_sup($tl)){ // $delay < $tl
        if ($hour->is_sup($this->week[$day][$i]->begin)){
          $tl->add($this->week[$day][$i]->begin ,$delay); // $sending_hour = $this->week[$day][$i]->begin + $delay;
        } else {
          $tl->add($hour ,$delay); // $sending_hour = $hour + $delay;
        }
        return [True, $tl];
      } else if ($delay->is_equal($tl)){
        return [True, $this->week[$day][$i]->end];
      } else {
        $delay->substract($delay, $tl);
      }
    }
    return [False, $delay];
  }

  public function __construct (){
    $this->week = array (
      "Monday" => array(),
      "Tuesday" => array(),
      "Wednesday" => array(),
      "Thursday" => array(),
      "Friday" => array(),
      "Saturday" => array(),
      "Sunday" => array()
    );
  }
}


/**
* Convert a configuration file to a Schedule object and return the created schedule
* @param string $file_name
* @return Schedule
*/
function convert_file($file_name){
  $fd = fopen($file_name, 'r');

  if ($fd){
    $schedule = new Schedule();

    while ($line = fgets($fd)){
      $data = explode(" ", $line);
      $data_size = count($data);
      if ($data_size%2 == 0){
        exit("The configuration file is malformed.\nFormat: 'Day Intervals' with Interval: 'HH:MM HH:MM'.");
      }
      for ($i = 1; $i < $data_size; $i+=2){
        $schedule->add_hour($data[0], $data[$i], $data[$i+1]);
      }
    }
    fclose($fd);
    return $schedule;
  } else {
    exit("The file can't be read.");
  }
}


/**
* Convert an ics file to an iCal object and sort the event to keep only current and coming holidays.
* The reference date is the acceptation date of the invoice.
* @param string $invoice_date
* @param string $iCal_file
* @return Array of EventObject
*/
function create_iCal($invoice_date, $iCal_file){
  $ical = new ICal($iCal_file);
  $events = $ical->eventsFromRange($invoice_date);
  //echo $events[0]->dtstart." ". $events[0]->dtend."\n";
  return $events;
 }


 /**
 * Check if a date given in parameters is during an event.
 * If True, return the date where holidays ends and remove the past event.
 * Else, return the date given in parameters.
 * @param Array of EventObject $events
 * @param DateTime $date
 * @return DateTime
 */
function date_during_holidays(&$events, $date) {
  $dtstart = DateTime::createFromFormat('Ymd H:i', $events[0]->dtstart.' 00:00');
  $dtend = DateTime::createFromFormat('Ymd H:i', $events[0]->dtend.' 00:00');
  if ($dtstart <= $date && $date < $dtend) {
    //echo $dtstart->format('Y-m-d H:i').' <= '.$date->format('Y-m-d H:i').' < '.$dtend->format('Y-m-d H:i')."\n";
    array_splice($events, 0, 1);
    return $dtend;
  }
  return $date;
}


/**
* Convert a configuration file to a Schedule object and return the created schedule
* @param Schedule $schedule
* @param string $invoice_date
* @param string $delay
* @return Array of EventObject $iCal
* @return Date
*/
function calculate_date($schedule, $invoice_date, $delay, $iCal){
  $date = DateTime::createFromFormat('Y-m-d H:i', $invoice_date);
  $result;
  if ($date){
      $delay_hour = new Hour();
      $delay_hour->str_to_hour($delay);
    do {  // Check for each day if the delay is consumed or not.
      $date = date_during_holidays($iCal, $date);
      $day = $date->format('l');
      $hour = new Hour($date->format('H'), $date->format('i'));
      $result = $schedule->check_calendar($day, $hour, $delay_hour);
      if($result[0] == True){
        $Ymd = $date->format('Y-m-d');
        return DateTime::createFromFormat('Y-m-d H:i', $Ymd.' '.sprintf('%02d', $result[1]->hour).':'.sprintf('%02d', $result[1]->minute));
      }
      $date->add(new DateInterval('P1D'));
      $date = DateTime::createFromFormat('Y-m-d H:i', $date->format('Y-m-d').' 00:00');
      $delay_hour = $result[1];
    } while ($result[0] == False);
  } else {
    exit("The date given in parameters is malformed.\nFormat: 'YYYY-MM-DD HH:MM'");
  }
}



function main($invoice_date, $delay='4:00', $file_name = "config.txt", $iCal_file = 'iCal_holidays.ics'){
  $schedule = convert_file($file_name);
  $iCal = create_iCal($invoice_date, $iCal_file);
  $date = calculate_date($schedule, $invoice_date, $delay, $iCal);

  echo $date->format('l').' '.$date->format('Y-m-d H:i')."\n";
}


/*
* In order to use this script with a single command line
*/
switch (count($argv)) {
  // Always at least 1 parameters: the name of the php file.
  case 1:
    echo "Usage: php sendingDate.php 'YYYY-MM-DD HH:MM'\n";
    echo "Optional: php sendingDate.php 'YYYY-MM-DD HH:MM' 'HH:MM' 'config_file_name' 'iCal_file_name'\n";
    break;
  case 2:
    main($argv[1]);
    break;
  case 3:
    main($argv[1], $argv[2]);
    break;
  case 4:
    main($argv[1], $argv[2], $argv[3]);
    break;
  default: // 5 or more (parameters after the 5th are ignored)
    main($argv[1], $argv[2], $argv[3], $argv[4]);
    break;
}

?>
