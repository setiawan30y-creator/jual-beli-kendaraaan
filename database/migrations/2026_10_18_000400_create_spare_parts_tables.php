<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stok sparepart berbasis ledger pergerakan (Blueprint: "Siklus Unit, Biaya Perbaikan, Stok Sparepart").
 * Saldo & harga rata-rata di spare_parts adalah ringkasan yang selalu diperbarui bersama movement
 * dalam satu transaksi database; ledger inventory_movements adalah sumber kebenaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 40);
            $table->string('name');
            $table->string('category', 40)->nullable(); // oli, ban, rem, kelistrikan, body, mesin, aksesori, lainnya
            $table->string('uom', 12)->default('pcs');
            $table->string('compatible_with')->nullable();
            $table->string('location', 60)->nullable(); // rak/gudang
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('qty_on_hand', 12, 2)->default(0);
            $table->decimal('avg_cost', 15, 2)->default(0); // moving weighted average
            $table->string('status', 12)->default('active');
            $table->timestamps();
            $table->unique(['tenant_id', 'sku']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spare_part_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // purchase_in, usage_out, usage_return_in, adjustment_in, adjustment_out
            $table->decimal('qty', 12, 2); // selalu positif; arah ditentukan type
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->decimal('balance_after', 12, 2);
            $table->foreignId('unit_id')->nullable()->constrained('vehicle_units')->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete(); // pemasok
            $table->string('reference_no', 60)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('reverses_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tenant_id', 'spare_part_id', 'id']);
            $table->index(['tenant_id', 'unit_id']);
        });

        Schema::table('vehicle_cost_entries', function (Blueprint $table) {
            $table->foreignId('inventory_movement_id')->nullable()->after('reverses_id')->constrained('inventory_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_cost_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_movement_id');
        });
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('spare_parts');
    }
};
