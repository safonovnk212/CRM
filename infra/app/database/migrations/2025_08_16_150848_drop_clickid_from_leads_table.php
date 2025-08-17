<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable("leads") && Schema::hasColumn("leads", "clickid")) {
            Schema::table("leads", function (Blueprint $table) {
                $table->dropColumn("clickid");
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable("leads") && !Schema::hasColumn("leads", "clickid")) {
            Schema::table("leads", function (Blueprint $table) {
                $table->string("clickid")->nullable();
            });
        }
    }
};
