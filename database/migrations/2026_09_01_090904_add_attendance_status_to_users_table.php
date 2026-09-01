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
            $table->enum('attendance_status', ['勤務外', '出勤中', '休憩中', '退勤済'])->default('勤務外')->after('remember_token');
            $table->boolean('admin_status')->default(false)->nullable()->after('attendance_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('attendance_status');
            $table->dropColumn('admin_status');
        });
    }
};
