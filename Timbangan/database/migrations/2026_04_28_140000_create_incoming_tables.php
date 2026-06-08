<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add operator type and shift type to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipe')->default('fg')->after('role'); // fg, incoming_singkong, incoming_rmpm
            $table->string('shift_type')->default('normal')->after('shift_end'); // normal, long
            $table->boolean('session_locked')->default(false)->after('shift_type'); // lock after stop
        });

        // Incoming Singkong table
        Schema::create('incoming_singkongs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_penimbangan');
            $table->string('no_surat');
            $table->string('nama_supplier');
            $table->string('asal');
            $table->string('nama_sopir');
            $table->string('nomor_plat');
            $table->string('jenis_singkong');
            $table->string('kode_produksi');
            $table->decimal('berat', 10, 3)->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->enum('status', ['menunggu', 'selesai', 'invalid'])->default('menunggu');
            $table->timestamps();
        });

        // Incoming RMPM table
        Schema::create('incoming_rmpms', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_kedatangan');
            $table->string('petugas_penerima');
            $table->string('nama_barang');
            $table->enum('jenis_barang', ['raw_material', 'packaging_material', 'lainnya'])->default('raw_material');
            $table->string('asal');
            $table->string('nama_supplier');
            $table->string('no_surat');
            $table->string('nama_sopir');
            $table->string('nomor_plat');
            $table->integer('total_qty')->default(0);
            $table->string('kode_batch')->nullable();
            $table->date('expired_date')->nullable();
            $table->decimal('berat', 10, 3)->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->enum('status', ['menunggu', 'selesai', 'invalid'])->default('menunggu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_rmpms');
        Schema::dropIfExists('incoming_singkongs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'shift_type', 'session_locked']);
        });
    }
};
