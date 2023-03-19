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
            $table->boolean('regular_expense')->default(false);
            $table->renameColumn('extra_income', 'secondary_income');
            $table->boolean('extra_expense')->default(false)->change();
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
            $table->dropColumn('regular_expense');
            $table->renameColumn('secondary_income', 'extra_income');
            $table->boolean('extra_expense')->default(true)->change();
        });
    }
};
