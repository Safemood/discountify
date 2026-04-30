<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('discountify.tables.promo_usages', 'discountify_promo_usages'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promo_id')
                ->constrained(config('discountify.tables.promos', 'discountify_promos'))
                ->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->timestamp('used_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('discountify.tables.promo_usages', 'discountify_promo_usages'));
    }
};
