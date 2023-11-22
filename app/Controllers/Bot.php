<?php

namespace App\Controllers;

use App\Models\HotelsModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\BotModel;
use App\Models\LocationModel;
use App\Models\RoomsModel;
use DateTime;

class Bot extends Controller
{
    public function botApi()
    {
        //seting connections
        $request = \Config\Services::request();
        $data = json_decode(file_get_contents("php://input"));

        //grabing the sender and message
        $message =  isset($data->query->message) ? $data->query->message : $request->getGet('message');
        $sender =   isset($data->query->sender) ? $data->query->sender : $request->getGet('sender');


        if (is_null($sender)  || is_null($message)) {
            return $this->response->setJSON(["error" => "parameters can't be null"]);
        }

        $session = $this->getSession($sender);

        if (strtolower($message) == 'clear') {
            $model = new BotModel();
            $model->delete($session["id"]);
            return $this->askLocation();
        }

        if (strtolower($message) == 'stop') {
            $this->update_status($sender);
            $res["replies"][]["message"] = "You have been added to bot ignore list, you won't receive message from bot again.\n\n\nTo start receiving bot help again, reply *clear*";
            return $this->response->setJSON($res);
        }

        if ($session["status"] != 'active') {
            return $this->response->setJSON(["error" => "user upted out of bot"]);
        }

        //start core operations
        $nextAction = $this->checkNextAction($sender);

        if ($nextAction == "location") {
            if ($this->locSearch($message) != false) {
                if ($this->update_location($sender, $this->locSearch($message)) == true) {
                    //update the session and continue
                    $session = $this->getSession($sender);
                    $nextAction = $this->checkNextAction($sender);
                } else {
                    return $this->askLocation();
                }
            } else {
                return $this->askLocation();
            }
        }


        if ($nextAction == "type") {
            if ($this->typeSearch($message) != false) {
                if ($this->update_type($sender, $this->typeSearch($message)) == true) {
                    //update the session and continue
                    $session = $this->getSession($sender);
                    $nextAction = $this->checkNextAction($sender);
                } else {
                    return $this->askType();
                }
            } else {
                return $this->askType();
            }
        }


        if ($nextAction == "start_date") {
            if ($this->getDate($message) != false) {
                if ($this->update_checkInDate($sender, $message) == true) {
                    //update the session and continue
                    $session = $this->getSession($sender);
                    $nextAction = $this->checkNextAction($sender);
                } else {
                    return $this->askCheckInDate();
                }
            } else {
                return $this->askCheckInDate();
            }
        }

        if ($nextAction == "end_date") {
            if ($this->getCheckOutDate($message, $session["start_date"]) != false) {
                if ($this->update_checkOutDate($sender, $message) == true) {
                    //update the session and continue
                    $session = $this->getSession($sender);
                    $nextAction = $this->checkNextAction($sender);
                } else {
                    return $this->askCheckOutDate();
                }
            } else {
                return $this->askCheckOutDate();
            }
        }

        if ($nextAction == "adult") {
            if ($this->getAdults($message) != false) {
                if ($this->update_adults($sender, $this->getAdults($message))) {
                    //update the session and continue
                    return $this->askChildren();
                } else {
                    return $this->askAdults();
                }
            } else {
                return $this->askAdults();
            }
        }

        if ($nextAction == "child") {
            if ($this->getChildren($message) != false) {
                if ($this->update_child($sender, $this->getChildren($message))) {
                    //update the session and continue
                    $session = $this->getSession($sender);
                    $nextAction = $this->checkNextAction($sender);
                } else {
                    return $this->askChildren();
                }
            } else {
                return $this->askChildren();
            }
        }

        if ($nextAction == "search_hotel") {
            $search = new Search();
            return $this->response->setJSON($search->searchHotels($session));
        }

        if ($nextAction == "search_events") {
            $search = new Search();
            return $this->response->setJSON($search->searchEvents($session));
        }

        if ($nextAction == "search_spaces") {
            $search = new Search();
            return $this->response->setJSON($search->searchSpaces($session));
        }


        ///
    }

    public function checkNextAction($sender)
    {
        $session = $this->getSession($sender);

        if ($session["location_id"] == null) {
            return "location";
        }

        if ($session["type"] == null) {
            return "type";
        }

        if ($session["start_date"] == null) {
            return "start_date";
        }

        if ($session["end_date"] == null) {
            return "end_date";
        }

        if ($session["adult"] == null) {
            return "adult";
        }

        if ($session["child"] == null) {
            return "child";
        }

        if ($session["type"] == "hotel") {
            return "search_hotel";
        }

        if ($session["type"] == "spaces") {
            return "search_spaces";
        }

        if ($session["type"] == "event" || $session["type"] == "tour") {
            return "search_events";
        }

        return "search_hotel";
    }

    public function checkActionCompleted()
    {
    }
    private function getSession(string $sender)
    {
        $model = new BotModel();
        $session = $model->where('user_id', $sender)->first();
        if (!$session) {
            $session = [
                'user_id' => $sender
            ];
            $model->insert($session);
        }
        $session = $model->where("user_id", $sender)->first();

        return $session;
    }
    private function update_location($sender, $loc_id)
    {

        $botModel = new BotModel();
        $locModel = new LocationModel();

        if (isset($loc_id)) {
            $number = $loc_id;
            $check = $locModel->where("id", $number)->first();
            if (!empty($check)) {
                $botModel->set("location_id", $number);
                $botModel->where("user_id", $sender);
                $botModel->update();
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }




    private function update_type($sender, $type)
    {
        $botModel = new BotModel();

        if (isset($type)) {
            $botModel->set("type", $type);
            $botModel->where("user_id", $sender);
            $botModel->update();
            return true;
        } else {
            return false;
        }
    }


    private function update_checkInDate($sender, $message)
    {
        $botModel = new BotModel();
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $message, $matches)) {
            $dateString = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            if ($this->isValidDate($dateString)) {
                $botModel->set("start_date", $dateString);
                $botModel->where("user_id", $sender);
                $botModel->update();
                return true;
            } else {
                return false;
            }
        }
    }
    private function update_status($sender)
    {
        $botModel = new BotModel();
        $botModel->set("status", "inactive");
        $botModel->where("user_id", $sender);
        $botModel->update();
        return true;
    }

    private function update_adults($sender, $adult)
    {
        $botModel = new BotModel();
        $botModel->set("adult", $adult - 1);
        $botModel->where("user_id", $sender);
        $botModel->update();
        return true;
    }
    private function update_child($sender, $child)
    {
        $botModel = new BotModel();
        $botModel->set("child", $child - 1);
        $botModel->where("user_id", $sender);
        $botModel->update();
        return true;
    }


    private function update_checkOutDate($sender, $message)
    {
        $botModel = new BotModel();
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $message, $matches)) {
            $dateString = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            if ($this->isValidDate($dateString)) {
                $botModel->set("end_date", $dateString);
                $botModel->where("user_id", $sender);
                $botModel->update();
                return true;
            } else {
                return false;
            }
        }
    }

    private function askLocation()
    {
        $locM = new LocationModel();

        $locations = $locM->where("status", 'publish')->findAll();
        $res['replies'] = [];
        $res['replies'][]['message'] =  "*I'm Tangodom's WhatsApp Bot* 🤖\n\n\n*Below are some helpful tips*\n\n\nTo talk to human instead, reply *stop*\n\n To get help, reply *help*\n\nTo reset your chat session or switch back to bot, reply *clear*";
        $n = 1;
        foreach ($locations as $location) {
            $loc[] = $n . ". " . $location["name"];

            $n++;
        }
        $res['replies'][]['message'] =  "*Below are the list of our available locations*\n\n\n" . implode("\n\n", $loc) . "\n\n\n_kindly reply with your preferred location_";

        return $this->response->setJSON($res);
    }


    private function askCheckInDate()
    {

        $date = date('d/m/Y', strtotime('+1 day'));
        $res['replies'][]["message"] = "*When do you plan to check in?*\n\n\n```Please reply using the following format: day/month/year```\n\n\n_Example: {$date}_";
        return $this->response->setJSON($res);
    }

    private function getDate(string $message)
    {

        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $message, $matches)) {

            $dateString = "{$matches[3]}-{$matches[2]}-{$matches[1]}";

            if ($this->isValidDate($dateString)) {

                $date = new DateTime($dateString);
                $today = new Datetime(date('Y-m-d'));

                if ($date < $today) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }
    }

    private function getCheckOutDate(string $message, $checkInDate)
    {

        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $message, $matches)) {

            $checkOutDateString = "{$matches[3]}-{$matches[2]}-{$matches[1]}";

            if ($this->isValidDate($checkOutDateString)) {

                $checkOutDate = new DateTime($checkOutDateString);
                $checkInDate = new DateTime($checkInDate);

                $interval = $checkOutDate->diff($checkInDate);
                $daysDiff = $interval->days;

                if ($daysDiff >= 1) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    private function askCheckOutDate()
    {
        $date = date('d/m/Y', strtotime('+2 day'));
        $res['replies'][]["message"] = "*When do you plan to check out?*\n\n\n```Please reply using the following format: day/month/year```\n\n\n_Example: {$date}_";
        return $this->response->setJSON($res);
    }

    private function askAdults()
    {

        $res['replies'][]["message"] = "How many adults?";
        return $this->response->setJSON($res);
    }

    private function askType()
    {

        $res['replies'][]["message"] = "*What are you looking for?*\n\n\n1 - A place to stay (Hotel, Homestays, Apartments)\n\n2 - Spaces (Offices, Rehearsal Rooms, Studios)\n\n3- Events\n\n4- Tours";
        return $this->response->setJSON($res);
    }

    private function getAdults(string $message)
    {
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $message, $matches)) {
            return false;
        } else {
            if (preg_match('/(\d+)/', $message, $matches)) {
                $numberOfAdults = (int)$matches[1];
                return $numberOfAdults + 1;
            } else {
                return false;
            }
        }
    }

    private function askChildren()
    {
        $res['replies'][]["message"] = "How many children?";
        return $this->response->setJSON($res);
    }


    private function getChildren(string $message)
    {
        if (preg_match('/(\d+)/', $message, $matches)) {
            $numberOfChild = (int)$matches[1];
            return $numberOfChild + 1;
        } else {
            return false;
        }
    }



    function locSearch($message)
    {
        $loc = new LocationModel();
        $search =  $loc->like("name", $message)->first();
        if (!empty($search)) {
            return $search["id"];
        } else {
            return false;
        }
    }


    function typeSearch($string)
    {

        $string = strtolower($string);

        $hotel_keywords = ['a place to stay', 'hotel', 'hotels', 'homestays', 'apartments'];
        $spaces_keywords = ['spaces', 'offices', 'rehearsal rooms', 'studios'];
        $event_keywords = ['events', 'event'];
        $tour_keywords = ['tour', 'tours'];

        $type = false;

        if ($this->hasMatches($string, $hotel_keywords)) {
            $type = "hotel";
        } elseif ($this->hasMatches($string, $spaces_keywords)) {
            $type = "spaces";
        } elseif ($this->hasMatches($string, $event_keywords)) {
            $type = "event";
        } elseif ($this->hasMatches($string, $tour_keywords)) {
            $type = "tour";
        }

        return $type;
    }

    function hasMatches($string, $keywords)
    {
        foreach ($keywords as $word) {
            if (strpos($string, $word) !== false) {
                return true;
            }
        }
        return false;
    }



    function isValidDate($dateString)
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $dateString);

        // Check if the date string matches the expected format and is a valid date
        return $dateTime && $dateTime->format('Y-m-d') === $dateString;
    }
}
