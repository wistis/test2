<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use GuzzleHttp\Client as HttpClient;

class User extends Authenticatable
{
  use HasApiTokens, HasFactory, Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
      'name',
      'email',
      'phone',
      'role_id',
      'description',
      'avatar',
      'lang_id'
  ];

  /** "name": "Антон",
   * "last_name": null,
   * "email": "ceo1@wistis.ru",
   * "phone": null,
   * "company": null
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
      'password',
      'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
      'email_verified_at' => 'datetime',
  ];

  public function sendpush($title, $body, $task_id, $type, $role, $org_id, $user_id = NULL)
  {
    $sendpush = new Sendedpush();
    if ($user_id) {
      $sendpush->user_id = $user_id;
    } else {
      $sendpush->user_id = $this->id;

    }

    $sendpush->from_user_id = $this->id;
    $sendpush->device_token = $this->device_token;
    $sendpush->task_id = $task_id;
    $sendpush->title = $title;
    $sendpush->text = $body;
    $sendpush->type = $type;
    $sendpush->role_id = $role;
    $sendpush->organization_id = $org_id;
    $sendpush->save();

    if (!$this->device_token) {
      return '';
    }
    if (!$this->show_notify) {
      return '';
    }
    $client = new HttpClient();

    $url = 'https://fcm.googleapis.com/fcm/send';
    $params['headers'] = [
        'Content-Type' => 'application/json',
        'Authorization' => 'key=AAAAbpzhmDQ:APA91bGpUekuPFLCpCIOSvnWGdKWk3yj6_ZUX_jtyiiNuejPfogmbjoR_CIoWK-k3Ip9bugjTpyAPD0YsH-DPc8zLbLJzv28AiEAqDXpweEEf-LfuEDpsXDf1z1Qh1_hfbaio2QtXnp9',
    ];

    $params['json']['notification']['body'] = $body;
    $params['json']['notification']['title'] = $title;
    $params['json']['notification']['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
    $params['json']['notification']['sound'] = 'default';
    $params['json']['priority'] = 'high';
    $params['json']['sound'] = 'default';
    $params['json']['data']['body'] = $body;
    $params['json']['data']['title'] = $title;
    $params['json']['data']['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
    $params['json']['data']['sound'] = 'default';
    $params['json']['to'] = $this->device_token;
    try {
      $response = $client->request('POST', $url, $params);
      $sendpush->response = $response->getBody()->getContents();
      $sendpush->save();
    } catch (\Exception $e) {
      $sendpush->response = $e->getMessage();
      $sendpush->save();
    }

  }

}
