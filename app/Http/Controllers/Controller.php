<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\Room;
use App\Models\Task;
use App\Models\UserTask;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  public function apierror($text)
  {
    return response()->json(['error' => $text], 422);
  }

  function send_mail($data)
  {

    \Mail::send(['html' => 'emails.repass'], $data, function($message) use ($data) {

      $message->to($data['email'], '');

      $message->subject($data['subject']);
    });
  }

}
