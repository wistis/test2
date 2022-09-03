<?php

namespace App\Http\Controllers;

use App\Models\Authors;
use App\Models\Company;
use App\Models\Course;
use App\Models\CourseLike;
use App\Models\CourseUser;
use App\Models\Lang;
use App\Models\User;
use Auth;

class UserController extends Controller
{
  public function get_authors()
  {
    $aut = Authors::where('user_id', auth()->user()->id)->first();
    if ($aut) {
      return $this->apierror(['ваша заявка отправлена']);
    }

    return response()->json([
        'success' => true,

    ], 200);
  }

  public function set_authors()
  {
    $validator = \Validator::make(request()->all(), [
        'about' => ['string', 'required'],
        'social' => ['string', 'required'],

    ]);
    if (!$validator->passes()) {

      return response()->json(['error' => $validator->errors()], 422);
    }

    $aut = Authors::where('user_id', auth()->user()->id)->first();
    if ($aut) {
      return $this->apierror(['ваша заявка отправлена']);
    }
    $aut = new Authors();
    $aut->user_id = auth()->user()->id;
    $aut->about = request('about');
    $aut->social = request('social');
    $aut->save();
    $datae['email'] = env('ADMIN_EMAIL');
    $datae['subject'] = 'Приглашение';

    $datae['text'] = 'USER ID: ' . $aut->user_id . ' 
      <br> О себе:' . $aut->about . '
      <br>Соц сети:' . $aut->social;
    $this->send_mail($datae);

    return response()->json([
        'success' => true,

    ], 200);

  }

  public function set_device_token()
  {

    auth()->user()->device_token = request('device_token');
    auth()->user()->device = request('device');
    auth()->user()->save();

    return response()->json([
        'success' => true,

    ], 200);

  }

  public function get_author($id)
  {
    $user = User::where('role_id', 2)->find($id);
    if (!$user) {
      return $this->apierror(['author not found']);
    }

    $data = $this->get(0, $user);

    return $data;

  }

  public function get($need_token = null, $user = null)
  {

    if (!$user) {
      $user = Auth::user();
    }
    if (!$need_token) {
      $token = $user->createToken('spa');
    }

    $data['name'] = $user->name;
    $data['email'] = $user->email;
    $data['phone'] = $user->phone;
    $data['avatar'] = $user->avatar;
    $data['description'] = $user->description;
    $data['role_id'] = $user->role_id;
    $data['lang_id'] = $user->lang_id;
    $courses = Course::where('user_id', $user->id);
    if ($user->role_id == 2) {
      if (auth()->check() && auth()->user()->id == $user->id) {
        $data['courses'] = $courses->get();
        $data['total_users'] = CourseUser::whereHas('course', function($q) use ($user) {
          $q->where('user_id', $user->id);
        })->groupby('user_id')->count();
        $data['total_likes'] = CourseLike::whereHas('course', function($q) use ($user) {
          $q->where('user_id', $user->id);
        })->count();
        if (count($data['courses']) > 0) {
          $data['total_rating'] = round($data['courses']->sum('rating') / count($data['courses']), 2);
        } else {
          $data['total_rating'] = 0;
        }
      } else {

        $data['courses'] = $courses->where('status', 1)->get();
      }

    }

    if (!$need_token) {
      return [
          'user' => $data,
          'token' => $token->plainTextToken,

      ];
    }

    return $data;

  }

  public function save()
  {

    $validator = \Validator::make(request()->all(), [
        'name' => ['string', 'max:255'],
        'email' => ['string', 'email', 'max:255'],
        'avatar' => ['image', 'mimes:jpg,png,jpeg', 'max:5120'],
        'description' => ['string'],
        'lang_id' => ['string']
    ]);
    $dataupdate = [];
    if ($validator->passes()) {

      if (request()->has('email')) {
        if (User::where('id', '!=', auth()->user()->id)->where('email', request()->email)->first()) {
          return response()->json(['errors' => ['Email занят'],], 422);
        }
        $dataupdate['email'] = request('email');

      }
      if (request()->has('name')) {

        $dataupdate['name'] = request('name');
      }

      if (request()->has('description')) {

        $dataupdate['description'] = request('description');
      }
      if (request()->hasFile('avatar')) {

        $file = request()->file('avatar');

        $filename = uniqid() . '.' . $file->clientExtension();

        $full_path = '/avatars/';
        $file->storeAs($full_path, $filename, ['disk' => 'public']);
        $dataupdate['avatar'] = env('APP_URL') . '/storage' . $full_path . $filename;
      }
      if (request()->has('delete_avatar')) {
        auth()->user()->avatar = NULL;
        auth()->user()->save();
      }
      if (request()->has('lang_id')) {
        $lang = Lang::where('code', request('lang_id'))->first();
        if ($lang) {
          $dataupdate['lang_id'] = request('lang_id');
        } else {
          return response()->json(['errors' => ['Язык не найден'],], 422);
        }
      }

      if (count($dataupdate) > 0) {

        User::where('id', auth()->user()->id)->update($dataupdate);
        $user = User::where('id', auth()->user()->id)->first();
      }

      return response()->json(['success' => true, 'user' => $this->get(1, $user)]);

    } else {
      return response()->json(['error' => $validator->errors()], 422);
    }

  }

  public function sendsupport()
  {

    $validator = \Validator::make(request()->all(), [
        'email' => ['string', 'max:255', 'required'],
        'mess' => ['string', 'required'],

    ]);

    if ($validator->passes()) {
      $datae['email'] = env('ADMIN_EMAIL');
      $datae['subject'] = 'Обращение в тп';

      $datae['text'] = '
      <br> E-mail:' . request('email') . '
      <br>Сообщение:' . request('mess');
      $this->send_mail($datae);

      return response()->json(['success' => true,]);

    } else {
      return response()->json(['error' => $validator->errors()], 422);
    }

  }
}