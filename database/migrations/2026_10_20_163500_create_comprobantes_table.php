<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('tipo');          // 11 = Factura C
            $table->unsignedSmallInteger('punto_venta');
            $table->unsignedBigInteger('numero')->nullable();
            $table->date('fecha');
            $table->decimal('importe_total', 12, 2);
            $table->unsignedSmallInteger('doc_tipo');      // 99 = sin identificar
            $table->unsignedBigInteger('doc_nro')->default(0);
            $table->unsignedSmallInteger('condicion_iva_receptor'); // 5 = Consumidor Final
            $table->string('cae', 20)->nullable();
            $table->date('cae_vencimiento')->nullable();
            $table->string('estado', 20);                 // autorizado | rechazado | error
            $table->text('mensaje')->nullable();
            $table->json('respuesta')->nullable();
            $table->timestamps();

            $table->unique(['tipo', 'punto_venta', 'numero']);
            $table->index(['sale_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
