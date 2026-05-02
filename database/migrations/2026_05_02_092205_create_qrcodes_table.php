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
        Schema::create('qrcodes', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code');
            $table->foreignId('penimbangan_id')->constrained('penimbangans')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('nama_sopir')->constrained('sopirs')->onDelete('cascade');
            $table->foreignId('nama_supplier')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->decimal('berat', 10, 3)->default(0);
            $table->decimal('selisih', 10, 3)->default(0);
            $table->enum('status', ['menunggu', 'selesai', 'invalid'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrcodes');
    }
};