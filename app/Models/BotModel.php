<?php

namespace App\Models;

use CodeIgniter\Model;

class BotModel extends Model
{
    protected $table = 'bot_follow_up';
    protected $allowedFields = ['id', 'user_id', 'location_id', 'notified', 'start_date', 'type', 'end_date', 'language', 'adult', 'comment', 'child', 'status', 'created_at', 'updated_at'];

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data)
    {
        $data['data']['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    protected function beforeUpdate(array $data)
    {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }



    //end
}
