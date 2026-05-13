<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rmpm_items', function (Blueprint $table) {
            $table->id();
            $table->string('type');          // 'nama_barang' or 'asal'
            $table->string('nama_barang');
            $table->timestamps();

            $table->unique(['type', 'nama_barang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rmpm_items');
    }
};
