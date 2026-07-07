<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_hash', 64)->nullable()->index();
            $table->enum('status', ['pending', 'processing', 'done', 'error', 'validated'])->default('pending');
            $table->json('products')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('product_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_imports');
    }
};
