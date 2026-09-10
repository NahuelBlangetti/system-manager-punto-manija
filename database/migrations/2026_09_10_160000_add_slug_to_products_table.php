<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        $products = DB::table('products')->select('id', 'name', 'slug')->get();
        $used = [];

        foreach ($products as $product) {
            $base = Str::slug((string) $product->name) ?: 'producto-'.$product->id;
            $slug = $base;
            $i = 2;

            while (isset($used[$slug])) {
                $slug = $base.'-'.$i;
                $i++;
            }

            $used[$slug] = true;
            DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
