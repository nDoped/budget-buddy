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
        Schema::create('category_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('note')->nullable();
            $table->string('hex_color', 7)->default('#ff00ff');
            $table->foreignId('user_id')
                  ->constrained()
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'regular_expense')) {
                $table->dropColumn('regular_expense');
            }
            if (Schema::hasColumn('categories', 'recurring_expense')) {
                $table->dropColumn('recurring_expense');
            }
            if (Schema::hasColumn('categories', 'extra_expense')) {
                $table->dropColumn('extra_expense');
            }
            if (Schema::hasColumn('categories', 'primary_income')) {
                $table->dropColumn('primary_income');
            }
            if (Schema::hasColumn('categories', 'secondary_income')) {
                $table->dropColumn('secondary_income');
            }
            if (Schema::hasColumn('categories', 'housing_expense')) {
                $table->dropColumn('housing_expense');
            }
            if (Schema::hasColumn('categories', 'utility_expense')) {
                $table->dropColumn('utility_expense');
            }

            $table->foreignId('category_type_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
            $table->dropForeign(['category_type_id']);
            $table->dropColumn('category_type_id');
            $table->boolean('regular_expense')->default(false);
            $table->boolean('recurring_expense')->default(false);
            $table->boolean('extra_expense')->default(false);
            $table->boolean('primary_income')->default(false);
            $table->boolean('secondary_income')->default(false);
            $table->boolean('utility_expense')->default(false);
            $table->boolean('housing_expense')->default(false);
        });
        Schema::dropIfExists('category_types');
    }
};
