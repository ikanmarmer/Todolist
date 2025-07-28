<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->string('pdf_url');
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->index('invoice_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
