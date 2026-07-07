<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('supplier_id')->nullable()->after('category_id')->constrained('suppliers')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('products', 'unit')) {
            Schema::table('products', function (Blueprint $table) {
                $after = Schema::hasColumn('products', 'imei') ? 'imei' : 'barcode';
                $table->string('unit')->nullable()->default('unidad')->after($after);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'supplier_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('supplier_id');
            });
        }

        if (Schema::hasColumn('products', 'unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('unit');
            });
        }
    }
};
