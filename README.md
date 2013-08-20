wp-phone-home
=============

WordPress plugin that "phones home" with any new IP address information every few minutes.

=============
Make sure that you've added a cron job to your crontab to run wp-cron at regular intervals or this script will never fire off.
Additionally this script doesn't do anything to check if email is working or not, you may need to install an SMTP plugin if you aren't getting emails
