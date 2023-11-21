<?php

namespace App\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table = 'bravo_locations';
    protected $allowedFields = ['id', 'name', 'content', 'slug', 'image_id', 'map_lat', 'map_lng', 'map_zoom', 'status', '_lft', '_rgt', 'parent_id', 'create_user', 'update_user', 'deleted_at', 'origin_id', 'lang', 'created_at', 'updated_at', 'banner_image_id', 'trip_ideas'];

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
