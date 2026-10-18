<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master pihak: nasabah/pembeli dan sumber unit/pemasok dalam satu tabel dengan peran multi
 * (Blueprint: "Pemisahan Peran Pihak" & "Data Nasabah/Pelanggan").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type', 12)->default('perorangan'); // perorangan, perusahaan
            $table->json('roles'); // customer, vehicle_source, auction_house, supplier, service_vendor, business_partner
            $table->string('name');
            $table->string('id_type', 10)->nullable(); // ktp, sim, paspor (perorangan) / nib, npwp (perusahaan)
            $table->text('id_number_encrypted')->nullable();
            $table->string('id_number_hash', 64)->nullable();
            $table->string('id_number_last4', 4)->nullable();
            $table->string('birth_place', 60)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities');
            $table->string('pic_name')->nullable();  // perwakilan perusahaan
            $table->string('pic_phone', 30)->nullable();
            $table->string('identity_status', 25)->default('belum_lengkap'); // belum_lengkap, menunggu_verifikasi, terverifikasi, ditolak, kedaluwarsa
            $table->string('identity_note')->nullable();
            $table->foreignId('identity_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('identity_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('source_lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'id_number_hash']);
            $table->index(['tenant_id', 'phone']);
        });

        Schema::create('party_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->index();
            $table->string('category', 20); // ktp, sim, paspor, foto_orang, legalitas, pendukung
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 60);
            $table->unsignedInteger('size_bytes');
            $table->string('checksum', 64);
            $table->string('verification_status', 25)->default('menunggu_verifikasi');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['party_id', 'category']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('party_id')->nullable()->after('buyer_user_id')->constrained('parties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('party_id');
        });
        Schema::dropIfExists('party_documents');
        Schema::dropIfExists('parties');
    }
};
