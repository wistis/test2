<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Course;
use App\Models\CourseLike;
use App\Models\CourseUser;
use App\Models\User;
use App\Models\Viewhistory;
use Laravel\Sanctum\Sanctum;

class FrontCourseController extends Controller
{

  public function index()
  {

    $data['data'] = Course::with(['user', 'dopcategory', 'category'])->where('status', 1)->paginate(10);

    return $data;
  }

  public function courses()
  {
    $validator = \Validator::make(request()->all(), [
        'finished' => ['required'],
    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $data['data'] = Course::with(['user', 'dopcategory', 'category' ])->where('status', 1)
        ->whereHas('courseuser',function($q){
          $q->where('user_id',auth()->user()->id);
          if(request('finished')==0) {
            $q->whereNULL('finished_at');
          }
          if(request('finished')==1) {
            $q->whereNOTNULL('finished_at' );
          }
        })

        ->paginate(15);
    return $data;
    //

  }
  public function views()
  {
    $validator = \Validator::make(request()->all(), [
        'phone_uid' => ['required'],
    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $data['data'] = Viewhistory::has('course')->with('course')->where('phone_uid', request('phone_uid'))->paginate(10);

    return $data;
    //

  }

  public function show($id)
  {
    $course=Course::with(['user', 'dopcategory', 'category', 'cards'])->where('status', 1)->find($id);
    if (!$course) {
      return $this->apierror(['Ошибка ддоступа']);
    }
    $data['data'] = Course::compact_one($course);


    $validator = \Validator::make(request()->all(), [
        'phone_uid' => ['required'],
    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $viewhistory = Viewhistory::where('phone_uid', request('phone_uid'))->where('course_id', $id)->first();
    if (!$viewhistory) {
      $viewhistory = new Viewhistory();
      $viewhistory->phone_uid = request('phone_uid');
      $viewhistory->course_id = $id;
      if (auth()->user()) {
        $viewhistory->user_id = auth()->user()->id;
      }
      $viewhistory->save();
    }

    return $data;

  }

  public function start()
  {

    $validator = \Validator::make(request()->all(), [
        'course_id' => ['required'],
    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $course = Course::where('status', 1)->find(request('course_id'));
    if (!$course) {
      return $this->apierror(['course not found']);
    }
    $course_user = CourseUser::where('course_id', $course->id)->where('user_id', auth()->user()->id)->first();
    if ($course_user) {
      $data['success'] = true;

      return $data;
    }
    if ($course->is_closed) {
      if (!request()->has('code') || request('code') != $course->code) {
        return $this->apierror(['error code']);
      }

    }
    $course_user = new CourseUser();
    $course_user->course_id = $course->id;
    $course_user->user_id = auth()->user()->id;
    $course_user->save();
    $data['success'] = true;

    return $data;
  }

  public function learn()
  {
    $validator = \Validator::make(request()->all(), [
        'course_id' => ['required'],
        'card_id' => ['required'],
    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $course = Course::where('status', 1)->find(request('course_id'));
    if (!$course) {
      return $this->apierror(['course not found']);
    }
    $course_user = CourseUser::where('course_id', $course->id)->where('user_id', auth()->user()->id)->first();
    if (!$course_user) {
      return $this->apierror(['course not your course']);
    }
    $mincard = Card::where('course_id', $course->id)->first();
    $maxcard = Card::where('course_id', $course->id)->orderby('id', 'desc')->first();
    if (!$course_user->card_id) {
      if (request()->card_id != $mincard->id) {
        return $this->apierror(['you mast learn card number ' . $mincard->card_number]);
      }
      $course_user->card_id = $mincard->id;
      $course_user->card_number = $mincard->card_number;
      if ($maxcard->id = $mincard->id) {
        $course_user->finished_at = now();
      }

      $course_user->save();
      $data['success'] = true;

      return $data;
    }
    if ($maxcard->id == $course_user->card_id) {
      if (!$course_user->finished_at) {
        $course_user->finished_at = now();
        $course_user->save();
      }
      $data['success'] = true;

      return $data;
    }
    $nextcard = Card::where('course_id', $course->id)->where('id', '>', $course_user->card_id)->orderby('id', 'asc')->first();

    if (request()->card_id != $nextcard->id) {
      return $this->apierror(['you mast learn card number ' . $nextcard->card_number]);
    }
    $course_user->card_id = $nextcard->id;
    $course_user->card_number = $nextcard->card_number;
    if ($maxcard->id = $nextcard->id) {
      $course_user->finished_at = now();
    }

    $course_user->save();
    $data['success'] = true;

    return $data;
  }

  public function rating()
  {
    $validator = \Validator::make(request()->all(), [
        'course_id' => ['required'],
        'rating' => ['required'],
    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $course = Course::where('status', 1)->find(request('course_id'));
    if (!$course) {
      return $this->apierror(['course not found']);
    }
    $course_user = CourseUser::where('course_id', $course->id)->where('user_id', auth()->user()->id)->first();
    if (!$course_user) {
      return $this->apierror(['course not your course']);
    }
    if (!$course_user->finished_at) {
      return $this->apierror(['fourse mast be finished']);
    }
    if (request('rating') < 1) {
      return $this->apierror(['rating mast be in 1-5']);
    }
    if (request('rating') > 5) {
      return $this->apierror(['rating mast be in 1-5']);
    }
    $course_user->rating = request('rating');
    $course_user->save();

    $sum=CourseUser::where('course_id',$course->id)->sum('rating');
    $count=CourseUser::where('course_id',$course->id)->whereNOTNULL('rating')->count();
    $rarting=0;
    if($count>0){
      $rarting=$sum/$count;

    }
    $course->rating=$rarting;
    $course->save();
    $data['success'] = true;

    return $data;

  }
  public function like()
  {
    $validator = \Validator::make(request()->all(), [
        'course_id' => ['required'],
    ]);
    if (!$validator->passes()) {
      return response()->json(['error' => $validator->errors()], 422);
    }

    $course = Course::where('status', 1)->find(request('course_id'));
    if (!$course) {
      return $this->apierror(['course not found']);
    }
    $course_user = CourseUser::where('course_id', $course->id)->where('user_id', auth()->user()->id)->first();
    if (!$course_user) {
      return $this->apierror(['course not your course']);
    }

    $clike=CourseLike::where('course_id',$course->id)->where('user_id',auth()->id())->first();
    if($clike){
      $clike->delete();
      $data['success'] = 'unlike';
    }else{
      $clike=new CourseLike();
      $clike->course_id=$course->id;
      $clike->user_id=auth()->id();
      $clike->save();
      $data['success'] = 'liked';
    }







    return $data;

  }
}