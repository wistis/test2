<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Models\Country;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Http\Request;

class CoursesController extends Controller
{
  public $course = NULL;
  public $card = NULL;

  public function index()
  {

    $data['data'] = Course::with(['user', 'cards', 'dopcategory', 'category'])->where('user_id', auth()->user()->id)->paginate(10);

    return $data;
  }

  public function error_course()
  {
    if (request()->has('id')) {
      $course = Course::with(['user', 'cards', 'dopcategory', 'category'])->where('user_id', auth()->user()->id)->where('id', request()->id)->first();
      if (!$course) {
        return 1;
      }
      $this->course = Course::compact_one($course);
    }

    return NULL;
  }

  public function error_card()
  {
    if (request()->has('card_id')) {
      $card = Card::where('user_id', auth()->user()->id)->where('id', request()->card_id)->first();
      if (!$card) {
        return 1;
      }
      $this->card = $card;
    }

    return NULL;
  }

  public function steps($id)
  {
    if ($this->error_course()) {
      return $this->apierror(['Курс не пренадлежит вас']);
    }
    if ($this->error_card()) {
      return $this->apierror(['Экрасн не пренадлежит вас']);
    }

    if (auth()->user()->role_id != 2) {
      return $this->apierror(['пользователь не имеет роли автора']);
    }
    if ($this->course && $id < 6 && $this->course->created_at < date('Y-m-d H:i:s', strtotime('-5 days'))) {
      return $this->apierror(['Нельзя редактировать курсы старще 5 дней']);

    }

    switch ($id) {
      case 1:
        return $this->step_1();
      case 2:
        return $this->step_2();
      case 3:
        return $this->step_3();
      case 4:
        return $this->step_4();
      case 5:
        return $this->step_5();
      case 6:
        return $this->step_6();

    }

  }

  public function step_1()
  {
    $validator = \Validator::make(request()->all(), [
        'name' => ['string', 'required'],

    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }
    if ($this->course) {
      $this->course->name = request('name');
      $this->course->save();
    } else {
      $this->course = new Course();
      $this->course->rating=0;
      $this->course->name = request('name');
      $this->course->user_id = auth()->user()->id;
      $this->course->status = 0;
      $this->course->save();

    }
    $data['course'] = $this->course;

    return $data;
  }

  public function step_2()
  {
    $validator = \Validator::make(request()->all(), [
        'description' => ['string', 'required'],
        'id' => ['integer', 'required'],

    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $this->course->desc = request('description');
    $this->course->save();

    $data['course'] = $this->course;

    return $data;
  }

  public function step_3()
  {
    $validator = \Validator::make(request()->all(), [
        'id' => ['integer', 'required'],
        'video' => ['mimes:mp4,ogx,oga,ogv,ogg,webm,avi'],
        'image' => ['image'],
        'audio' => ['mimes:mp3,wav'],

    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    if (!$this->card) {
      $this->card = new Card();
      $this->card->user_id = auth()->user()->id;
      $this->card->course_id = $this->course->id;
      $max = Card::where('course_id', $this->course->id)->max('card_number');
      $max++;
      $this->card->card_number = $max;

    }
    $this->card->text = request('text');

    if (request()->has('video')) {
      $video = new Video();
      $video->user_id = auth()->user()->id;
      $video->key = md5(auth()->user()->id . time() . microtime() . rand(1, 9999990) . rand(1, 9999990));
      $video->status = 0;
      $video->save();
      $file = request()->file('video');
      $filename = uniqid() . '.' . $file->clientExtension();

      $full_path = '/courses/videos/';
      $file->storeAs($full_path, $filename, ['disk' => 'public']);
      $video->file = $full_path . $filename;
      $video->save();
      $this->card->type = 'video';
      $this->card->video = $video->key;
    }
    if (request()->has('image')) {
      $file = request()->file('image');
      $filename = uniqid() . '.' . $file->clientExtension();

      $full_path = '/courses/images/';
      $file->storeAs($full_path, $filename, ['disk' => 'public']);
      $this->card->image = env('APP_URL') . '/storage/' . $full_path . $filename;
      $this->card->type = 'image';

    }
    if (request()->has('audio')) {
      $file = request()->file('audio');
      $filename = uniqid() . '.' . $file->clientExtension();

      $full_path = '/courses/audio/';
      $file->storeAs($full_path, $filename, ['disk' => 'public']);
      $this->card->image = env('APP_URL') . '/storage/' . $full_path . $filename;

    }
    $this->card->save();

    $data['card'] = $this->card;
    $data['course'] = $this->course;

    return $data;
  }

  public function step_4()
  {
    $validator = \Validator::make(request()->all(), [
        'id' => ['integer', 'required'],
        'categoryes' => ['array'],
        'default_category' => ['string', 'required'],

    ]);

    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }
    $cats = [];
    if (request()->has('categoryes')) {
      $cats = request('categoryes');
    }
    $cats[] = request('default_category');
    $array_cats = [];
    $category_id = NULL;
    foreach ($cats as $cat) {
      $categ = Category::where('name', $cat)->first();
      if (!$categ) {
        $categ = new Category();
        $categ->name = $cat;
        $categ->save();
      }
      if ($cat == request('default_category')) {
        $category_id = $categ->id;
      }
      $array_cats[] = [
          'course_id' => $this->course->id,
          'category_id' => $categ->id,
      ];
    }

    $this->course->category_id = $category_id;
    $this->course->dopcategory()->sync($array_cats);

    $this->course->save();

    $data['course'] = $this->course;

    return $data;
  }

  public function step_5()
  {
    $validator = \Validator::make(request()->all(), [
        'id' => ['integer', 'required'],
        'is_closed' => ['boolean'],

    ]);

    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $this->course->is_closed = \request('is_closed');
    $this->course->code = request('code');

    $this->course->save();

    $data['course'] = $this->course;

    return $data;
  }

  public function step_6()
  {
    $validator = \Validator::make(request()->all(), [
        'id' => ['integer', 'required'],
        'status' => ['boolean'],

    ]);

    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }
    if (\request('status') == 1) {

      if (!$this->course->category_id) {
        return $this->apierror(['Категория обязательна']);
      }
      if (count($this->course->cards) == 0) {
        return $this->apierror(['Карточки обязательны']);
      }

    }

    $this->course->status = \request('status');

    $this->course->save();

    $data['course'] = $this->course;

    return $data;
  }

  public function deletecard()
  {
    if ($this->error_card()) {
      return $this->apierror(['Экран не пренадлежит вас']);
    }

    $this->card->delete();
    $i = 1;
    $cards = Card::where('course_id', $this->card->course_id)->orderby('id')->get();
    foreach ($cards as $card) {
      $card->card_number = $i;
      $card->save();
      $i++;
    }
    $data['success'] = true;

    return $data;
  }

  public function getcard($id)
  {
    $this->card = Card::where('id', $id)->where('user_id', auth()->user()->id)->first();
    if (!$this->card) {
      return $this->apierror(['Экран не пренадлежит вас']);
    }

    $data['card'] = $this->card;

    return $data;
  }

  public function getone($id)
  {
    $course = Course::where('id', $id)->with(['user', 'cards', 'dopcategory', 'category'])->where('user_id', auth()->user()->id)->first();

    if (!$course) {
      return $this->apierror(['Курс не пренадлежит вас']);
    }

    $data['course'] = Course::compact_one($course);

    return $data;
  }

  public function delete()
  {
    if ($this->error_course()) {
      return $this->apierror(['Курс не пренадлежит вас']);

    }
    Card::where('course_id', $this->course->id)->delete();
    $this->course->delete();
    $data['success'] = true;

    return $data;

  }
}

