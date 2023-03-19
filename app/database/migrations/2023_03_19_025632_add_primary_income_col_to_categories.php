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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('primary_income')->default(false);
            $table->boolean('extra_income')->default(false);
            $table->boolean('housing_expense')->default(false);
            $table->boolean('utility_expense')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('primary_income');
            $table->dropColumn('extra_income');
            $table->dropColumn('housing_expense');
            $table->dropColumn('utility_expense');
        });
    }
};
