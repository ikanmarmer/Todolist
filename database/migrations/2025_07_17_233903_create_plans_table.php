<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_plans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->integer('tasks_limit');
            $table->string('color')->default('#06b6d4'); // Default cyan color
            $table->boolean('is_popular')->default(false);
            $table->json('features')->nullable(); // JSON array of features
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plans');
    }
};
