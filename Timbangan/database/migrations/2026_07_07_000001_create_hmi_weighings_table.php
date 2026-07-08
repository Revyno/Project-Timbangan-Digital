<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel terpadu untuk alur HMI (PRINT). Satu baris = satu hasil timbangan
     * yang di-print, mencakup menu "Bahan Baku" & "Formulasi" (dan menu HMI
     * lain menyusul). Sengaja dipisah dari `penimbangans`/`incoming_rmpms`
     * agar dashboard & eksport lama tidak terganggu, sekaligus menghindari
     * batasan `penimbangans.kode_produksi` yang unik (tak muat banyak
     * timbangan per-batch). Kolom mengikuti tabel "Penyimpanan Di excel".
     */
    public function up(): void
    {
        Schema::create('hmi_weighings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                       // idempotency key lintas server
            $table->string('menu')->index();                      // bahan-baku | formulasi | ...
            $table->string('site')->default('default');           // asal/pabrik pengirim

            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('operator_name');
            $table->string('karu_name')->nullable();              // hanya formulasi
            $table->string('shift')->nullable();
            $table->date('tanggal');

            $table->string('produk')->nullable();                 // parent produk (formulasi)
            $table->string('nama_item');                          // bahan yang ditimbang
            $table->string('kode_batch')->nullable();

            $table->decimal('target', 10, 3)->nullable();
            $table->decimal('berat', 10, 3);
            $table->string('unit', 8)->default('kg');             // kg | gr
            $table->decimal('selisih', 10, 3)->nullable();
            $table->unsignedInteger('timbangan_ke')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'failed'])->default('pending')->index();
            $table->timestamp('synced_at')->nullable();
            $table->unsignedBigInteger('online_id')->nullable();  // id baris di server online

            $table->timestamps();

            $table->index(['menu', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmi_weighings');
    }
};
