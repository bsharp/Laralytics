<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateLaralyticsUrlTable
 */
class CreateLaralyticsUrlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laralytics_url', function (Blueprint $table) {

            $engine = Config::get('database.default');

            $table->increments('id');
            $table->integer('user_id')->index()->nullable();
            $table->string('session', 250)->nullable();
            $table->string('hash', 64)->index();
            $table->string('host', 255)->index();
            $table->string('path', 255);
            $table->string('method', 10)->index();

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
        Schema::drop('laralytics_url');
    }
}
