<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('nomor_plat')->nullable();
            $table->string('qr_code')->unique();
            $table->timestamps();
        });

        Schema::create('weigh_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->decimal('berat', 10, 3)->default(0);
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weigh_logs');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('suppliers');
    }
};
