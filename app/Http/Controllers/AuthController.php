<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrationRequest;
use App\Models\Company;
use App\Models\Lang;
use App\Models\PassReeset;
use App\Models\UserChangeEmail;
use App\Models\UserNotificationSetting;
use App\Models\UserSocial;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Auth;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{

  public function check()
  {
    $validator = \Validator::make(request()->all(), [
        'phone' => ['required'],
        'country_id' => ['required'],

    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }
    $phone = $this->format_phone(request('phone'));
    $user = User::where('country_id', request('country_id'))
        ->where('phone', $phone)->first();
    if (!$user) {
      $user = new User();
      $user->phone = $phone;
      $user->role_id = 1;
      $user->country_id = request()->country_id;
      $user->lang_id = env('LANG_ID');
      $user->save();
    }
    $user->password = rand(1000, 9999);
    $user->save();
    $data['success'] = true;
    $data['code'] = $user->password;

    try {
      $text='code: ' . $user->password;
      $s = file_get_contents('https://' . env('SENDPULS_API_USER_ID') . ':' . env('SENDPULS_API_SECRET') . '@gate.smsaero.ru/v2/sms/send?number=7' . $phone . '&text='.$text.'&sign=SMS Aero');
      $r = json_decode($s, true);
      if (isset($r['success']) && $r['success'] == 'true') {
        return $data;
      }else{
        info('sms send error');
        info($s);
        return $this->apierror(['sms send error']);
      }

    } catch (\Exception $e) {

      return $this->apierror(['server sms error']);
    }

    return $data;
  }

  public function login()
  {
    $validator = \Validator::make(request()->all(), [
        'phone' => ['required'],
        'country_id' => ['required'],
        'code' => ['required'],
        'phone_uid' => ['required'],

    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $phone = $this->format_phone(request('phone'));
    $u = User::where('country_id', request('country_id'))
        ->where('phone', $phone)->where('password', request('code'))->first();
    if (!$u) {
      return $this->apierror(['error code']);
    }

  //  \Auth::loginUsingId($u->id);
    $u->phone_uid = request('phone_uid');
    $u->password = md5(time() . rand(1, 99999));
    $u->save();
    $data1 = new UserController();

    $array = $data1->get(0,$u);
    $array['message'] = __('message.registration');
    $array['success'] = true;
    $array['id'] = $u->id;

    return response()->json($array, 200);

    return response()->json(['error' => $validator->errors()], 422);

  }

  public function format_phone($phone)
  {

    $phone = str_replace('+', '', $phone);
    $phone = str_replace('-', '', $phone);
    $phone = str_replace(')', '', $phone);
    $phone = str_replace('(', '', $phone);
    $phone = str_replace(' ', '', $phone);
    // $phone = preg_replace('/^8/', '7', $phone);

    /*if (strlen($phone) == 9) {
      $phone =  $phone;
    }*/

    return $phone;

  }

}
