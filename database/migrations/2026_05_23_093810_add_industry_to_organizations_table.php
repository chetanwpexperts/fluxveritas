<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('industry_type')->default('technology')->after('name');
            $table->string('org_size')->default('startup')->after('industry_type');
            $table->boolean('tech_focus')->default(true)->after('org_size');
            $table->string('website')->nullable()->after('tech_focus');
            $table->string('city')->nullable()->after('website');
            $table->string('country')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['industry_type', 'org_size', 'tech_focus', 'website', 'city', 'country']);
        });
    }
};
