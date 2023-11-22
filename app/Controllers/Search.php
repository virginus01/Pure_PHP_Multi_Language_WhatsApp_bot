<?php

namespace App\Controllers;

use App\Models\EventsModel;
use App\Models\HotelsModel;
use CodeIgniter\Controller;
use App\Models\RoomsModel;
use App\Models\LocationModel;
use App\Models\SpacesModel;

class Search extends Controller
{
    public function searchHotels(array $session)
    {
        $hotelsM = new HotelsModel();
        $roomsM = new RoomsModel();

        $hotels = $hotelsM->where("location_id", $session["location_id"])->where("status", "publish")->findAll();


        $res['replies'] = [];
        if (!empty($hotels)) {

            $res['replies'][]['message'] = "*These are the rooms available for you in " . $this->locName($session["location_id"]) . "*";


            foreach ($hotels as $key => $hotel) {
                $name = $hotel["title"];
                $rooms = $roomsM->where("parent_id", $hotel["id"])->where("status", "publish")->findAll();
                $hRooms = array();
                foreach ($rooms as $room) {
                    $hRooms[] = "*" . $room["title"] . "Room.*\n\nPrice: " . $this->custom_num_to_currency($room["price"]) . "/per day" . "\n\nBed(s): " . $room["beds"];
                }

                $res['replies'][]['message'] = "🏨 *" . $hotel["title"] . "*\n\n*Available Rooms at " . $hotel["title"] . " currently*\n\n\n" . implode("\n\n", $hRooms) . "" . "\n\n_If you are interrested in {$name}, follow this link to make a room reservation or enquiry: " . $this->baseUrl("hotel/" . $hotel["slug"]) . "_";
            }
            $res['replies'][]['message'] = "Perform another search by replying *clear* or *stop* to talk to human instead and we'll contact you as soon as possible";
        } else {
            $res['replies'][]['message'] = "We could not find a hotel for you. Perform another search by replying *clear* or *stop* to talk to human and we'll contact you as soon as possible";
        }

        return $res;
    }

    public function searchEvents(array $session)
    {
        $eventsM = new EventsModel();


        $events = $eventsM->where("location_id", $session["location_id"])->where("status", "publish")->findAll();


        $res['replies'] = [];

        if (!empty($events)) {

            $res['replies'][]['message'] = "*These are the activities available for you in " . $this->locName($session["location_id"]) . "*";
            foreach ($events as $key => $event) {
                $name = $event["title"];
                $res['replies'][]['message'] = "*" . $event["title"] . "*\n\n*Price:* " . $this->custom_num_to_currency($event["price"]) . "\n\n\n_If you are interrested in {$name}, follow this link to make a reservation or enquiry: " . $this->baseUrl("event/" . $event["slug"]) . "_";
            }
            $res['replies'][]['message'] = "Perform another search by replying *clear* or *stop* to talk to human instead and we'll contact you as soon as possible";
        } else {
            $res['replies'][]['message'] = "We could not find an event for you. Perform another search by replying *clear* or *stop* to talk to human and we'll contact you as soon as possible";
        }

        return $res;
    }



    public function searchSpaces(array $session)
    {
        $speacesM = new SpacesModel();


        $spaces = $speacesM->where("location_id", $session["location_id"])->where("status", "publish")->findAll();


        $res['replies'] = [];

        if (!empty($spaces)) {
            $res['replies'][]['message'] = "*These are the spaces available for you in " . $this->locName($session["location_id"]) . "*";
            foreach ($spaces as $key => $space) {
                $name = $space["title"];
                $res['replies'][]['message'] = "*" . $space["title"] . "*\n\n*Price:* " . $this->custom_num_to_currency($space["price"]) . "\n\n\n_If you are interrested in {$name}, follow this link to make a reservation or enquiry: " . $this->baseUrl("space/" . $space["slug"]) . "_";
            }
            $res['replies'][]['message'] = "Perform another search by replying *clear* or *stop* to talk to human instead and we'll contact you as soon as possible";
        } else {
            $res['replies'][]['message'] = "We could not find a space for you. Perform another search by replying *clear* or *stop* to talk to human and we'll contact you as soon as possible";
        }

        return $res;
    }

    function baseUrl($path)
    {
        $url = "https://tangodom.online/" . $path;
        return $url;
    }



    function locName($id)
    {
        $loc = new LocationModel();
        $search =  $loc->where("id", $id)->first();

        if (!empty($search)) {
            return $search["name"];
        } else {
            return false;
        }
    }

    public  function custom_num_to_currency($string = false)
    {

        $result = "$" . number_format($string);
        return $result;
    }
    //end
}
