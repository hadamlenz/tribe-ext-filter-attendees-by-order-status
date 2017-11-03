<?php
/**
 * Plugin Name:     Event Tickets Plus Extension: Filter attendees by order status
 * Description:     Adds a filter to the attendees list
 * Version:         0.1
 * Extension Class: Tribe__Extension__Filter_Attendees_By_Order_Status
 * Author:          H. Adam Lenz
 * Author URI:      https://hadamlenz.wordpress.com
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */
defined( 'WPINC' ) or die;

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Filter_Attendees_By_Order_Status extends Tribe__Extension {
    
    public $event = false;
    public $attendees = false;


    public function construct() {
		$this->add_required_plugin( 'Tribe__Tickets__Main' );
		$this->add_required_plugin( 'Tribe__Tickets_Plus__Main' );
		$this->set_version( '1.0.1' );
        $this->set_url( 'https://fuu.bar/' );    

        // Fetch the event Object
		if ( ! empty( $_GET['event_id'] ) ) {
			$this->event = get_post( $_GET['event_id'] );
        }
        
        $this->tribe_data_api = new  Tribe__Tickets__Data_API();
    }
    /**
	 * Extension initialization and hooks.
	 */
	public function init() {

        add_filter( 'tribe_events_tickets_attendees_table_nav', array($this, 'tribe_events_tickets_attendees_table_nav'), 10, 2);
        add_filter( 'tribe_tickets_event_attendees', array($this, 'tribe_tickets_event_attendees'), 20, 2 );
        
    }

    /**
    * Adds the status drop down to the bottom attendees table.  Filter location: /event-tickets/src/Tribe/Attendees_Table
    *
    * @param array $nav The array of items in the nav, where keys are the name of the item and values are the HTML of the buttons/inputs.
    * @param string $which Either 'top' or 'bottom'; the location of the current nav items being filtered.
    */

    public function tribe_events_tickets_attendees_table_nav( $nav, $which ){
       if( "bottom" === $which ){
            //get all the statuses for all of the attendees
            //must remove the other filter or when filtered only that one status is returned here
            remove_filter( 'tribe_tickets_event_attendees', array($this, 'tribe_tickets_event_attendees'), 20, 2 );
            $this->attendees = Tribe__Tickets__Tickets::get_event_attendees( $this->event->ID );
            add_filter( 'tribe_tickets_event_attendees', array($this, 'tribe_tickets_event_attendees'), 20, 2 );

            //make a unique array of the statuses
            $status_array = $this->get_unique_order_status_labels($this->attendees);

            //die early if the array is empyt
            if( empty($status_array) ){
                return $nav;
            }

            ob_start();
            ?>
            
            <select id="tribe_attendees_order_status" name="tribe_attendees_order_status">
            <option value="all">All Statuses</option>
                <?php 
                $format = '<option value="%s">%s</option>';
                foreach($status_array as $k => $v ) {
                    echo sprintf(  $format, $k, $v );
                }
                ?>
            </select>
            <input type="submit" value="filter" class="button action">
            <?php
           $output = ob_get_contents();
           ob_end_flush();

           $nav[] = $output;
       }
       return $nav; 
    }

    public function tribe_tickets_event_attendees( $attendees, $event_id ){
        if( isset($_REQUEST['tribe_attendees_order_status'] ) ){
            $status = $_REQUEST['tribe_attendees_order_status'];
            $filtered_list = array();
            switch ( $status ) {
                case 'all':
                    return $attendees;
                    break;
                case 'deleted':
                    for( $i = 0; $i < count($attendees); $i++ ){
                        if( false == $attendees[$i]['order_status'] ) {
                            $filtered_list[] = $attendees[$i];
                        }
                    }
                    return $filtered_list;
                    break;
                default:
                    for( $i = 0; $i < count($attendees); $i++ ){
                        if( $status == $attendees[$i]['order_status'] ) {
                            $filtered_list[] = $attendees[$i];
                        }
                    }
                    return $filtered_list;
            }
        }
        return $attendees;
    }

    /**
    * Gets an array of unique statuses from the attendees
    *
    * @param array $attendees all the event attendees
    *
    * @return array
    */
    public function get_unique_order_status_labels($attendees){
        $status_array = array();
        foreach($attendees as $attendee){
            if( !in_array($attendee['order_status_label'], $status_array ) ) {
                $key = $attendee['order_status'];
                if(!$key){
                    $key = "deleted";
                }
                $status_array[$key] = $attendee['order_status_label'];
            }
        }
        return $status_array;
    }
    
}