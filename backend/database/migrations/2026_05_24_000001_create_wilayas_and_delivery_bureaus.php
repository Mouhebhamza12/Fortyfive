<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayas', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_bureaus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wilaya_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('commune')->nullable();
            $table->string('address');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_type')->default('home')->after('payment_method');
            $table->foreignId('wilaya_id')->nullable()->after('delivery_type')->constrained()->nullOnDelete();
            $table->foreignId('delivery_bureau_id')->nullable()->after('wilaya_id')->constrained()->nullOnDelete();
            $table->decimal('subtotal', 10, 2)->default(0)->after('total_amount');
            $table->decimal('shipping_fee', 10, 2)->default(0)->after('subtotal');
            $table->string('bureau_name')->nullable()->after('shipping_fee');
            $table->string('commune')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_bureau_id');
            $table->dropConstrainedForeignId('wilaya_id');
            $table->dropColumn([
                'delivery_type',
                'subtotal',
                'shipping_fee',
                'bureau_name',
                'commune',
            ]);
        });

        Schema::dropIfExists('delivery_bureaus');
        Schema::dropIfExists('wilayas');
    }
};
