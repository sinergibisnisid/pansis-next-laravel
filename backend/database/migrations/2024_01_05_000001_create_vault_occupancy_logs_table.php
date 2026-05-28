<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabel tracking masuk/keluar vault untuk hitung jumlah orang di dalam
return new class extends Migration {
    public function up(): void
    {
        Schema::create('vault_occupancy_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('vault_id')
                ->constrained('vaults')
                ->cascadeOnDelete();

            $table->foreignUuid('session_id')
                ->nullable()
                ->constrained('vault_sessions')
                ->nullOnDelete();

            // Siapa yang masuk (kalau teridentifikasi)
            $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Waktu masuk/keluar
            $table->timestamp('entered_at');
            $table->timestamp('exited_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            // Metode deteksi: fingerprint, door_sensor, manual, camera
            $table->string('entry_method', 32)->default('door_sensor');
            $table->string('exit_method', 32)->nullable();

            $table->string('notes')->nullable();

            $table->timestamps();

            $table->index(['vault_id', 'exited_at']);
            $table->index(['vault_id', 'entered_at']);
            $table->index('session_id');
        });

        // Tambah kolom max_occupancy di vaults
        Schema::table('vaults', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_occupancy')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vault_occupancy_logs');

        Schema::table('vaults', function (Blueprint $table) {
            $table->dropColumn('max_occupancy');
        });
    }
};