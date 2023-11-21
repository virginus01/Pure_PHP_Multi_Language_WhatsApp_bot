<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomsModel extends Model
{
    protected $table = 'bravo_hotel_rooms';
    protected $allowedFields = ['id', 'title', 'content', 'image_id', 'gallery', 'video', 'price', 'parent_id', 'number', 'beds', 'size', 'adults', 'children', 'status', 'create_user', 'update_user', 'deleted_at', 'created_at', 'updated_at', 'ical_import_url', 'min_day_stays'];

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
