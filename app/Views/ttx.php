<?php

use App\Models\LocationModel;

function locSearch($message)
{
    $loc = new LocationModel();
    $search =  $loc->like("name", $message)->orLike("id", $message)->first();

    if (!empty($search)) {
        return $search["id"];
    } else {
        return false;
    }
}

echo locSearch("Neuquen");
