<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('stock_quantity', 12, 2)->default(0)->after('purchase_price');
            $table->decimal('reorder_level', 12, 2)->default(0)->after('stock_quantity');
            $table->decimal('cost_price', 15, 2)->default(0)->after('reorder_level');
            $table->boolean('track_inventory')->default(false)->after('cost_price');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return', 'transfer']);
            $table->decimal('quantity', 12, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'reorder_level', 'cost_price', 'track_inventory']);
        });
    }
};
