<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccessLogEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('access_log_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_log_id');
            $table->string('ip_address', 15)->index();
            $table->string('http_method', 7)->index();
            $table->string('url', 1000);
            $table->dateTime('request_datetime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('access_log_entries');
    }
}
