<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("leads", function (Blueprint $table) {
            $table->index(["phone", "created_at"], "leads_phone_created_at");
        });
    }

    public function down(): void
    {
        Schema::table("leads", function (Blueprint $table) {
            $table->dropIndex("leads_phone_created_at");
        });
    }
};
