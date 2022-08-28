<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notebook', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('company')->nullable();
            $table->string('phone');
            $table->string('email');
            $table->string('date_of_birth')->nullable();
            $table->boolean('photo_available');
            $table->set('photo_type', ['image/png', 'image/jpeg'])->nullable();
            $table->binary('photo_content')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notebook');
    }
};
