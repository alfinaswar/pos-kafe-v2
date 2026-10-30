<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('products', fn(Blueprint $table) => $table->boolean('is_bundle')->default(false)->after('image')); } public function down(): void { Schema::table('products', fn(Blueprint $table) => $table->dropColumn('is_bundle')); } };
