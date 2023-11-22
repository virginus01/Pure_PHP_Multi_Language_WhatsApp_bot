<?php

namespace App\Models;

use CodeIgniter\Model;

class EventsModel extends Model
{
    protected $table = 'bravo_events';
    protected $allowedFields = ['id', 'title', 'slug', 'content', 'image_id', 'banner_image_id', 'location_id', 'address', 'map_lat', 'map_lng', 'map_zoom', 'is_featured', 'gallery', 'video', 'faqs', 'ticket_types', 'duration', 'start_time', 'price', 'sale_price', 'is_instant', 'enable_extra_price', 'extra_price', 'review_score', 'ical_import_url', 'status', 'default_state', 'create_user', 'update_user', 'deleted_at', 'created_at', 'updated_at', 'enable_service_fee', 'service_fee', 'surrounding', 'end_time', 'duration_unit', 'author_id'];

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
