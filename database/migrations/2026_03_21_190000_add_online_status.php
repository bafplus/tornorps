<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('war_members', function (Blueprint $table) {
            $table->string('online_status', 20)->default('offline')->after('ff_updated_at');
            $table->string('online_description')->nullable()->after('online_status');
        });
    }

    public function down(): void
    {
        Schema::table('war_members', function (Blueprint $table) {
            $table->dropColumn(['online_status', 'online_description']);
        });
    }
};
