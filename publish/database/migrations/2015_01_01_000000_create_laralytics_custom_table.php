<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateLaralyticsCustomTable
 */
class CreateLaralyticsCustomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laralytics_custom', function (Blueprint $table) {

            $engine = Config::get('database.default');

            $table->increments('id');
            $table->integer('user_id')->index()->nullable();
            $table->string('hash', 64)->index();
            $table->string('url', 255);
            $table->string('version', 255)->index();
            $table->string('event', 64)->index();
            $table->text('data');

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
        Schema::drop('laralytics_custom');
    }
}