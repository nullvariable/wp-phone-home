<?php
/*
Plugin Name: WP Phone Home
Plugin URI: http://dougcone.com
Description: This plugin sends an email any time it detects an IP address/network settings change on its side
Author: Doug Cone
Version: 1.0
Author URI: http://dougcone.com/
*/
define('WP_PHONE_HOME_ACTIVE', true); //this is just here because, this plugin shouldn't be active if you aren't wanting the function
define('WP_PHONE_HOME', 'wp_phone_home'); //option name
define('WP_PHONE_HOME_INTERVAL', 5); //number of minutes to wait in between checking IP changes
class wp_phone_home {
    /**
     * Do some namespaced actions and what not here.
     */
    function __construct() {
        if ( ! wp_next_scheduled('et_phone_home') && WP_PHONE_HOME_ACTIVE) {
            wp_schedule_event(time(), 'everyX', 'wp_phone_home');
        }
        add_filter('cron_schedules', array($this, 'every5'));
        add_action('wp_phone_home', array($this, 'phone_home_test'));
    }

    /**
     * Add our own custom cron interval to WordPress
     * @param $param
     * @return array
     * handy blog post: http://wpengineer.com/1908/use-wordpress-cron/
     */
    function everyX( $param ) {
        return array( 'everyX' => array(
            'interval' => WP_PHONE_HOME_INTERVAL * 60, // seconds
            'display'  => __( 'Every '.WP_PHONE_HOME_INTERVAL.' Minutes' )
        ) );
    }

    /**
     * This function is called by the cron hook and tests things to see if there is new information
     */
    function phone_home_test() {
        $ifconfig = shell_exec("/sbin/ifconfig");
        $message = "SERVER_ADDR = ".$_SERVER['SERVER_ADDR'];
        $message .= "\nifconfig: \n".$ifconfig;
        if ( $this->compare_ips(get_option(WP_PHONE_HOME), $message) ) {
            //the settings are different from the last message we sent, generate a new one
            $this->phone_home($message);
            update_option(WP_PHONE_HOME, $message);
        }
    }


    /**
     * function tests stored ifconfig to see if there are ips in the new ifconfig that aren't there
     * super handy website: http://www.regexplanet.com/advanced/php/index.html
     * @param $stored string
     * @param $current string
     * @return bool
     */
    function compare_ips($stored, $current) {
        $pattern = "/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/"; //not perfect, matches 999.999.999.999 but we don't actually care, we only care if it has changed.
        preg_match_all($pattern, $stored, $save_ips);
        preg_match_all($pattern, $current, $current_ips);
        if (!empty($save_ips[0])) {
            foreach ($save_ips[0] as $ip) {
                if (!in_array($ip, $current_ips[0])) {
                    return true; //this IP is missing, indicate that it is a new IP
                }
            }
        }
        return false; //all the IPs are apparently the same
    }

    /**
     * This function does the dirty work of sending our email through wp_mail
     * @param $message string to send off via email
     */
    function phone_home($message) {
        wp_mail(
            get_option('admin_email'),
            get_option("blogname") . __(" Phone Home"),
            $message
        );
    }
}
$wp_phone_home = new wp_phone_home();