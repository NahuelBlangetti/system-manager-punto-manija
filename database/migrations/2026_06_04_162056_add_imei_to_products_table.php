<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('products', 'imei')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('imei')->nullable()->after('barcode');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'imei')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('imei');
        });
    }
};
