<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 64);
            $table->string('name', 128)->nullable();
            $table->string('status', 32)->default('new'); // new|sent|approved|rejected
            $table->string('clickid', 128)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->index(['phone','status']);
            $table->index('clickid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
