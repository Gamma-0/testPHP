
# README

## Parameters
The main function take 4 parameters, all parameters are strings:

* invoice_date: when the invoice was approved.
  * Format is  *'YYYY-MM-DD HH:MM'*
* delay: the number of business hours before sending an email.
  * Format is *'HH:MM'*
  * Default value is *'4:00'*
* file_name: the name of the configuration file.
  * Default value is *'config.txt'*
* iCal_file: the name of the iCal file.
  * Default value is *'...'*

## Configuration file
This file contains 7 lines. Each line have the following format:  
*Day HH:MM HH:MM* ...

There is 1+2*h parameters per line:

* The first is the day written in english.
* Each following couple of hours is the begin and the end of the business hours. The hours **must** be ordered by increasing order.

Example:
> Monday 13:30 17:00  
> Tuesday 9:00 12:00 13:30 17:00  
> Wednesday 9:00 12:00 13:30 17:00  
> Thursday 9:00 12:00 13:30 17:00  
> Friday 9:00 12:00  
> Saturday  
> Sunday  

## How to use
### PHP interactive mode
In a terminal, use the command line:  
>`php -a -d auto_prepend_file=sendingDate.php`.

Then, use the main() function with the correct parameters.  
Some example:
>main('2017-02-10 10:00');

>main('2016-08-25 10:00', '2:50');
