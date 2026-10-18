<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog → physical unit → listing (Blueprint §4.2, §5.2–§5.4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('categories', 30)->default('motor'); // motor, mobil, motor,mobil
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('vehicle_brands')->cascadeOnDelete();
            $table->string('category', 10)->index(); // motor, mobil
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('body_type', 40)->nullable();   // matic, sport, bebek, naked, trail / mpv, suv, sedan, hatchback
            $table->unsignedInteger('engine_cc')->nullable();
            $table->string('transmission', 20)->nullable(); // manual, otomatis, semi
            $table->string('fuel_type', 20)->nullable();
            $table->json('specifications_json')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('vehicle_models')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('model_year')->nullable();
            $table->json('official_specs_json')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('tenant_branches')->nullOnDelete();
            $table->foreignId('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('model_id')->constrained('vehicle_models');
            $table->foreignId('variant_id')->nullable()->constrained('vehicle_variants')->nullOnDelete();
            $table->string('stock_code', 40)->nullable();
            $table->string('condition', 10); // new, used
            $table->unsignedSmallInteger('year');
            $table->string('color', 40)->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->text('frame_number_encrypted')->nullable();
            $table->text('engine_number_encrypted')->nullable();
            $table->string('frame_number_hash', 64)->nullable()->index();
            $table->string('plate_region', 10)->nullable();
            $table->string('registration_status', 30)->nullable(); // lengkap, stnk_hidup, stnk_mati, ...
            $table->string('ownership_type', 20)->default('owned'); // owned, consignment
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('asking_price', 15, 2);
            $table->string('status', 20)->default('available')->index(); // inspection, maintenance, available, reserved, sold, withdrawn
            $table->date('acquired_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('unit_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('vehicle_units')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->index();
            $table->string('category', 20); // bpkb, stnk, nomor_rangka, nomor_mesin, depan, belakang, kanan, kiri, orang_unit
            $table->string('disk', 20);     // local (private) | public
            $table->string('path');
            $table->string('mime', 60);
            $table->unsignedInteger('size_bytes');
            $table->string('checksum', 64);
            $table->string('verification_status', 25)->default('menunggu_verifikasi'); // menunggu_verifikasi, terverifikasi, perlu_perbaikan
            $table->boolean('publish_consent')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['unit_id', 'category']);
        });

        Schema::create('vehicle_cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('vehicle_units')->cascadeOnDelete();
            $table->string('category', 30); // pembelian, akuisisi, service, sparepart, transportasi, detailing, administrasi, lainnya
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('posted'); // estimate, posted, reversed
            $table->date('entry_date');
            $table->string('reference_no', 60)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reverses_id')->nullable()->constrained('vehicle_cost_entries')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'unit_id', 'status']);
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('seller_user_id')->constrained('users');
            $table->foreignId('unit_id')->constrained('vehicle_units')->cascadeOnDelete();
            $table->string('category', 10)->index(); // denormalised: motor, mobil
            $table->string('condition', 10)->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->boolean('negotiable')->default(true);
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->string('status', 20)->default('draft'); // draft, pending_review, published, rejected, reserved, sold, expired, archived
            $table->string('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->index(['status', 'category', 'condition', 'price']);
            $table->index(['status', 'city_id']);
        });

        Schema::create('listing_favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['user_id', 'listing_id']);
        });

        Schema::create('listing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 40);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('open'); // open, reviewed, dismissed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('form'); // form, whatsapp, booking, api
            $table->string('status', 20)->default('baru'); // baru, dihubungi, negosiasi, booking, menang, tidak_lanjut
            $table->string('buyer_name');
            $table->string('buyer_phone', 30);
            $table->text('message')->nullable();
            $table->boolean('contact_consent')->default(false);
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'assigned_user_id']);
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('activity_type', 30);
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('booking_type', 20)->default('kunjungan'); // kunjungan, test_ride
            $table->dateTime('starts_at');
            $table->string('status', 20)->default('menunggu'); // menunggu, dikonfirmasi, selesai, dibatalkan
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['bookings', 'lead_activities', 'leads', 'listing_reports', 'listing_favorites', 'listings',
            'vehicle_cost_entries', 'unit_media', 'vehicle_units', 'vehicle_variants', 'vehicle_models', 'vehicle_brands'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
