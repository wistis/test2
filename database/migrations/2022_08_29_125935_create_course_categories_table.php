<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('course_categories', function(Blueprint $table) {
      $table->id();
      $table->integer('course_id')->nullable();
      $table->integer('category_id')->nullable();


      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('course_categories');
  }
};