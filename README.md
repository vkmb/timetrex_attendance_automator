# Timetrex attendance automator

Timetrex Attendance Automator for Linux based systems using Crontab

### note: There is a catch in the newer versions.The user need to have administrative access to automate it. So do some social engineering ; ) before getting your hands dirty.

### To edit crontab file do as follows

1.Open terminal.

2.Type "crontab -e" (By default it opens in VIM) , to change to nano "export VISUAL=nano; crontab -e" do this.

#### -> A little detour 
#### Crontab file spec
##### * * * * * script to be executed
##### | | | | |_ denotes the day with respect to week
##### | | | |___ denotes the month
##### | | |_____ denotes the day with respect to month
##### | |_______ denotes the hours interval
##### |_________ denotes the minutes interval 

for more details : www.crontab.guru  (Awesome place to get started and end to.)

3.Copy paste the following statement 
##### 10 9,13,14,20 * * * /usr/bin/bash ~/timetrex_attendance_automator/punchIn.sh 
The above statement will execute at 9:10 AM, 1:10 PM, 2:10 PM, 8:10 PM all time of the year.  

4.Press CTRL+C and type "y" to save and exit the "NANO" editor
  Press CTRL+C and type ":wq" save and exit the "VIM" editor

5.Enter super user password to install the crontab.
______________________________________________________________________________________________________________________________
