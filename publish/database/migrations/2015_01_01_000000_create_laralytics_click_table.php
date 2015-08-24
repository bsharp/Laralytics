<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateLaralyticsClickTable
 */
class CreateLaralyticsClickTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laralytics_click', function (Blueprint $table) {

            $engine = Config::get('database.default');

            $table->increments('id');
            $table->string('uuid', 64)->index();
            $table->integer('x');
            $table->integer('y');
            $table->string('element', 255)->index()->nullable();

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
        Schema::drop('laralytics_click');
    }
}
