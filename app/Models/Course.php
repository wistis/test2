<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
  public function cards()
  {
    return $this->hasMany(Card::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function category()
  {
    return $this->belongsTo(Category::class);
  }

  public function dopcategory()
  {
    return $this->belongsToMany(Category::class, CourseCategory::class);
  }

  public function viewhistory()
  {
    return $this->hasOne(Viewhistory::class);
  }
  public function courseuser(){
    return $this->hasMany(CourseUser::class);
  }

  public static function compact_one($course){
  $course->total_user=CourseUser::where('course_id',$course->id)->count();
  $course->likes=CourseLike::where('course_id',$course->id)->count();
  if(auth()->check()){
  $course->this_user_course=CourseUser::where('course_id',$course->id)->where('user_id',auth()->user()->id)->first();
  $course->this_user_like=CourseLike::where('course_id',$course->id)->where('user_id',auth()->user()->id)->first();


  }

    return $course;
  }
}