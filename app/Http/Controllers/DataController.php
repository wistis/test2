<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Lang;

class DataController extends Controller
{
  public function countrys()
  {

    $data['data'] = Country::get();

    return $data;
  }

  public function langs()
  {

    $data['data'] = Lang::get();

    return $data;
  }

  public function category()
  {

    $cat = Category::orderby('name');
    if (request()->has('q') && request('q') != '') {
      $cat->where('name', 'LIKE', '%' . request('q') . '%');
    }
    $data['data'] = $cat->get();

    return $data;
  }
}