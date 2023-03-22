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
            if (Schema::hasColumn('categories', 'extra_income')) {
                $table->renameColumn('extra_income', 'secondary_income');
            }

            if (Schema::hasColumn('categories', 'extra_expense')) {
                $table->boolean('extra_expense')->default(false)->change();
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
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'regular_expense')) {
                $table->dropColumn('regular_expense');
            }
            if (Schema::hasColumn('categories', 'secondary_income')) {
                $table->renameColumn('secondary_income', 'extra_income');
            }
            if (Schema::hasColumn('categories', 'extra_expense')) {
                $table->boolean('extra_expense')->default(true)->change();
            }
        });
    }
};
