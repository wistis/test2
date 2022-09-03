<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up()
  {
    Schema::create('countries', function(Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('phonecode');
      $table->string('code');
      $table->string('icon');
      $table->string('mask');


      

      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('countries');
  }
};