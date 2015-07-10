<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateLaralyticsInfoTable
 */
class CreateLaralyticsInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laralytics_info', function (Blueprint $table) {

            $engine = Config::get('database.default');

            $table->increments('id');
            $table->integer('user_id')->index()->nullable();
            $table->string('session', 250)->nullable();
            $table->string('version')->index()->nullable();
            $table->string('browser');
            $table->integer('browser_width');
            $table->integer('browser_height');
            $table->integer('device_width');
            $table->integer('device_height');

            // Auto timestamp for postgreSQL and others
            if ($engine === 'pgsql') {
                $table->timestamp('created_at')->default(DB::raw('now()::timestamp(0)'));
            } else {
                $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('laralytics_info');
    }
}
