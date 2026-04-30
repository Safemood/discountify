<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('discountify.tables.conditions', 'discountify_conditions'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('field')->default('count');         // count|total|subtotal|<item key>
            $table->string('operator')->default('gte');        // ConditionOperator enum value
            $table->json('value');                             // scalar or array
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('discount_type')->default('percentage'); // DiscountType enum value
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('discountify.tables.conditions', 'discountify_conditions'));
    }
};
