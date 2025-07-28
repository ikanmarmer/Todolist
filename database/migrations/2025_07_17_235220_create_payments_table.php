<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('snap_token')->nullable();
            $table->string('transaction_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('transaction_status');
            $table->index('transaction_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
