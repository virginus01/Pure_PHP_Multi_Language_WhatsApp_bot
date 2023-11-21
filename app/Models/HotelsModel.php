<?php

namespace App\Models;

use CodeIgniter\Model;

class HotelsModel extends Model
{
    protected $table = 'bravo_hotels';
    protected $allowedFields = ['id', 'title', 'slug', 'content', 'image_id', 'banner_image_id', 'location_id', 'address', 'map_lat', 'map_lng', 'map_zoom', 'is_featured', 'gallery', 'video', 'policy', 'star_rate', 'price', 'check_in_time', 'check_out_time', 'allow_full_day', 'sale_price', 'status', 'create_user', 'update_user', 'deleted_at', 'created_at', 'updated_at', 'review_score', 'ical_import_url', 'enable_extra_price', 'extra_price', 'enable_service_fee', 'service_fee', 'surrounding', 'author_id', 'min_day_before_booking', 'min_day_stays'];

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
