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


        if ($this->locSearch($message) != false) {
            if ($this->update_location($sender, $this->locSearch($message)) == true) {
                $session = $this->getSession($sender);
                return $this->askCheckInDate($this->response);
            } else {
                return $this->askLocation();
            }
        } else if (!is_null($session["location_id"])) { {

                if ($this->getDate($message) == true) {

                    if (is_null($session["start_date"])) {
                        $this->update_checkInDate($sender, $message);
                        return $this->askCheckOutDate();
                    } else    if (is_null($session["end_date"]) && !is_null($session["start_date"])) {
                        $this->update_checkOutDate($sender, $message);
                        return   $this->askAdults();
                    } else {
                        return $this->askAdults();
                    }
                } else if (is_null($session["start_date"])) {
                    return $this->askCheckInDate($this->response);
                } else if (is_null($session["end_date"])) {
                    return $this->askCheckOutDate();
                } else {
                    if ($this->getAdults($message) != false && is_null($session["adult"])) {

                        $this->update_adults($sender, $this->getAdults($message));
                        return $this->askChildren();
                    } else if ($this->getChildren($message) != false && !is_null($session["adult"])) {
                        $this->update_child($sender, $this->getChildren($message));
                        return $this->response->setJSON($this->searchHotels($session));
                    } else {

                        return  $this->askChildren();
                    }
                }
            }
        } else {
            return $this->askLocation();
        }


        ///
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
        //checking if message is all about location
        //  preg_match('/LC(\d+)/', $message, $matches);
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
        $res['replies'][]['message'] =  "*Below are the list of our available locations*\n\n\n" . implode("\n\n", $loc) . "\n\n\n_kindly reply me your preferred location_";

        return $this->response->setJSON($res);
    }


    private function askCheckInDate($response)
    {

        $date = date('d/m/Y', strtotime('+1 day'));
        $res['replies'][]["message"] = "*When do you plan to check in?*\n\n\nExample: {$date}";
        return $this->response->setJSON($res);
    }

    private function getDate(string $message)
    {
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $message, $matches)) {
            $dateString = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            if ($this->isValidDate($dateString)) {
                return true;
            } else {
                return false;
            }
        }
    }

    private function askCheckOutDate()
    {
        $date = date('d/m/Y', strtotime('+2 day'));
        $res['replies'][]["message"] = "*When do you plan to check out?*\n\n\nExample: {$date}";
        return $this->response->setJSON($res);
    }

    private function askAdults()
    {

        $res['replies'][]["message"] = "How many adults?";
        return $this->response->setJSON($res);
    }

    private function getAdults(string $message)
    {
        if (preg_match('/(\d+)/', $message, $matches)) {
            $numberOfAdults = (int)$matches[1];
            return $numberOfAdults + 1;
        } else {
            return false;
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

    private function searchHotels(array $session)
    {
        $hotelsM = new HotelsModel();
        $roomsM = new RoomsModel();

        $hotels = $hotelsM->where("location_id", $session["location_id"])->where("status", "publish")->findAll();


        $res['replies'] = [];
        if (!empty($hotels)) {
            foreach ($hotels as $key => $hotel) {
                $rooms = $roomsM->where("parent_id", $hotel["id"])->where("status", "publish")->findAll();

                $hRooms = array();

                $res['replies'][]['message'] = "*Location:* " . $this->locName($session["location_id"]) . "\n\n*Duration:* " . date(("d/m/Y"),  strtotime($session["start_date"]))  . " - " . date(("d/m/Y"),  strtotime($session["end_date"])) . " (12 noon checkout)" . "\n\n*Adult(s):* " . $session["adult"] . "\n\n*Child(ren):* " . $session["child"];

                foreach ($rooms as $room) {
                    $hRooms[] = "*" . $room["title"] . ".*\n\n Price: " . $this->custom_num_to_currency($room["price"]) . "/per day\n\nSize: " . $room["size"] . "\n\nBed(s): " . $room["beds"];
                }

                $res['replies'][]['message'] = "*" . $hotel["title"] . "*\n\n*Link:* " . $this->baseUrl("hotels/" . $hotel["slug"]) . "\n\n*Available Rooms at " . $hotel["title"] . " currently*\n\n\n" . implode("\n\n", $hRooms) . "";

                $res['replies'][]['message'] = "Perform another search by replying *clear* or *stop* to talk to human instead";
            }
        } else {
            $res['replies'][]['message'] = "No Hotel found. Perform another search by replying *clear* or *stop* to talk to human";
        }

        return $res;
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

    function isValidDate($dateString)
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $dateString);

        // Check if the date string matches the expected format and is a valid date
        return $dateTime && $dateTime->format('Y-m-d') === $dateString;
    }


    public  function custom_num_to_currency($string = false)
    {

        $result = "$" . number_format($string);
        return $result;
    }
}
