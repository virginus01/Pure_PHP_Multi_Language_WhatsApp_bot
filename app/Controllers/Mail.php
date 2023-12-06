<?php

namespace App\Controllers;

use App\Models\BotModel;
use App\Models\EventsModel;
use App\Models\HotelsModel;
use CodeIgniter\Controller;
use App\Models\RoomsModel;
use App\Models\LocationModel;
use App\Models\SpacesModel;

class Mail extends BaseController
{
    public function sendMail($session, $info, $subject = "New Email", $isAboundoned = false)
    {

        $location = $this->getLocName($session['location_id']);

        $start_date = !is_null($session["start_date"]) ? date("y-m-d", strtotime($session["start_date"])) : null;
        $end_date =  !is_null($session["end_date"]) ? date("y-m-d", strtotime($session["end_date"])) : null;
        $adult = $session["adult"];
        $child = $session["child"];
        $type = $session["type"];
        $language = $session["language"];

        $msg = "<br/>Location: {$location}<br/><br/>Check-In Date: {$start_date}<br/><br/>Check-Out Date: {$end_date}<br/><br/>Adult: {$adult}<br/><br/>Child: {$child}<br/><br/>Type: {$type}<br/><br/>Language: {$language}";

        $body = "<html><body>{$info} <br/><br/> {$msg}<body></html>";

        $email = \Config\Services::email();


        $config['SMTPHost'] = env('SMTP_HOST');
        $config['SMTPUser'] = env('SMTP_USER');
        $config['SMTPPass'] = env('SMTP_PASS');
        $config['protocol'] = env('PROTOCOL');
        $config['SMTPPort'] = env('SMTP_PORT');
        $config['SMTPCrypto'] = env('SMTP_CRYPTO');

        $email->initialize($config);

        $email->setFrom(env('SMTP_USER'), "Bot");
        $email->setTo(env("receiver_email"));
        $email->setSubject($subject);
        $email->setMessage($body);


        $botModel = new BotModel();
        $email_sent = $email->send();

        if ($isAboundoned) {
            if ($email_sent) {
                $botModel->set("notified", 'yes');
                $botModel->where('user_id', $session["user_id"]);
                $botModel->update();
                return $email_sent;
            } else {
                $description = $email->printDebugger();
                $botModel->set("comment", $description);
                $botModel->where('user_id', $session["user_id"]);
                $botModel->update();
                return $description;
            }
        } else {
            if (!$email_sent) {
                $description = $email->printDebugger();
                $botModel->set("comment", $description);
                $botModel->where('user_id', $session["user_id"]);
                $botModel->update();
                return $description;
            }
        }

        return true;
    }



    function getLocName($id)
    {
        $loc = new LocationModel();
        $search =  $loc->where("id", $id)->first();

        if (!empty($search)) {
            return $search["name"];
        } else {
            return false;
        }
    }


    function mail_cron()
    {


        $botModel = new BotModel();
        $oneDayAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $process = $botModel
            ->where("child", null)
            ->where("notified", null)
            ->where("updated_at <", $oneDayAgo)
            ->paginate(10);
        foreach ($process as $pro) {
            $sender = $pro['user_id'];
            $message =  "<p>{$sender} has aboundoned the conversation. <a href='https://api.whatsapp.com/send/?phone={$sender}'>Click here to follow up</a></p>";
            $ms =    $this->sendMail($pro, $message, "Aboundoned Chat by {$sender}", true);
            print_r($ms);
        }
    }
}
