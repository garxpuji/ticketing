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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('technician_group_id')
                ->nullable()
                ->after('role')
                ->constrained('technician_groups')
                ->nullOnDelete();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('technician_group_id')
                ->nullable()
                ->after('assigned_technician_id')
                ->constrained('technician_groups')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['technician_group_id']);
            $table->dropColumn('technician_group_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['technician_group_id']);
            $table->dropColumn('technician_group_id');
        });
    }
};

