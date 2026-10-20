<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_register_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('external_reference')->unique();
            $table->string('terminal_id');
            $table->string('mp_order_id')->nullable()->unique();
            $table->string('mp_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            // created | at_terminal | processed | canceled | expired | failed | refunded | action_required | error
            $table->string('status')->default('pending');
            $table->string('status_detail')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_method_id')->nullable();
            $table->unsignedSmallInteger('installments')->nullable();
            $table->string('card_last_digits', 8)->nullable();
            // Carrito congelado al momento de enviar al posnet: la venta se
            // crea recién cuando Mercado Pago confirma el cobro.
            $table->json('sale_payload');
            $table->json('last_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_payments');
    }
};
