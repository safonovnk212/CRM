<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table("leads", function (Blueprint $table) {
            if (!Schema::hasColumn("leads", "click_id")) {
                $table->string("click_id", 255)->nullable()->after("phone");
            }
            if (!Schema::hasColumn("leads", "offer_id")) {
                $table->string("offer_id", 255)->nullable()->after("click_id");
            }
        });
    }
    public function down(): void {
        Schema::table("leads", function (Blueprint $table) {
            if (Schema::hasColumn("leads", "click_id")) {
                $table->dropColumn("click_id");
            }
            if (Schema::hasColumn("leads", "offer_id")) {
                $table->dropColumn("offer_id");
            }
        });
    }
};
