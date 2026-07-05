<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount'); // 正=賺取/派發，負=兌換扣點
            $table->enum('type', [
                'earn_homework',   // 作業完成獎勵（即時成熟）
                'redeem_course',   // 兌換課程扣點（負值，即時）
                'earn_referral',   // 推薦回饋（+，延遲成熟）
                'refund_reversal', // 退款作廢未成熟回饋（對銷）
                'admin_grant',     // 後台派發（正值，即時）
            ]);
            $table->string('reference_type', 30)->nullable(); // 'order' | 'assignment' | 'admin'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('available_at');       // 成熟可用時間
            $table->timestamp('created_at');         // write-once，無 updated_at
            $table->boolean('matured_synced')->default(false); // 是否已計入 users.points 快取

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'available_at']);
            $table->index(['type', 'available_at', 'matured_synced']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
