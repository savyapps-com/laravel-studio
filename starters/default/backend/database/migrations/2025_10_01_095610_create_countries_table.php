<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->id();
                $table->string('code', 2)->unique();
                $table->string('code_alpha3', 3)->unique();
                $table->string('numeric_code', 3);
                $table->string('name');
                $table->json('native_name')->nullable();
                $table->string('capital')->nullable();
                $table->string('region');
                $table->string('subregion')->nullable();
                $table->string('currency_code', 3)->nullable();
                $table->string('currency_name')->nullable();
                $table->string('currency_symbol')->nullable();
                $table->string('phone_code')->nullable();
                $table->string('flag_emoji', 10)->nullable();
                $table->string('flag_svg')->nullable();
                $table->json('languages')->nullable();
                $table->string('tld')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_eu_member')->default(false);
                $table->integer('display_order')->default(999);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['region', 'subregion']);
                $table->index('is_active');
                $table->index('display_order');
            });
        } else {

            if (! Schema::hasColumn('countries', 'code')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('code', 2)->unique()->after('id');
                });
            }
            if (! Schema::hasColumn('countries', 'code_alpha3')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('code_alpha3', 3)->unique()->after('code');
                });
            }
            if (! Schema::hasColumn('countries', 'numeric_code')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('numeric_code', 3)->after('code_alpha3');
                });
            }
            if (! Schema::hasColumn('countries', 'name')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('name')->after('numeric_code');
                });
            }

            if (! Schema::hasColumn('countries', 'native_name')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->json('native_name')->nullable()->after('name');
                });
            }
            if (! Schema::hasColumn('countries', 'capital')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('capital')->nullable()->after('native_name');
                });
            }
            if (! Schema::hasColumn('countries', 'region')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('region')->after('capital');
                });
            }
            if (! Schema::hasColumn('countries', 'subregion')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('subregion')->nullable()->after('region');
                });
            }
            if (! Schema::hasColumn('countries', 'currency_code')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('currency_code', 3)->nullable()->after('subregion');
                });
            }
            if (! Schema::hasColumn('countries', 'currency_name')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('currency_name')->nullable()->after('currency_code');
                });
            }
            if (! Schema::hasColumn('countries', 'currency_symbol')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('currency_symbol')->nullable()->after('currency_name');
                });
            }
            if (! Schema::hasColumn('countries', 'phone_code')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('phone_code')->nullable()->after('currency_symbol');
                });
            }
            if (! Schema::hasColumn('countries', 'flag_emoji')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('flag_emoji', 10)->nullable()->after('phone_code');
                });
            }
            if (! Schema::hasColumn('countries', 'flag_svg')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('flag_svg')->nullable()->after('flag_emoji');
                });
            }
            if (! Schema::hasColumn('countries', 'languages')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->json('languages')->nullable()->after('flag_svg');
                });
            }
            if (! Schema::hasColumn('countries', 'tld')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->string('tld')->nullable()->after('languages');
                });
            }
            if (! Schema::hasColumn('countries', 'latitude')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('tld');
                });
            }
            if (! Schema::hasColumn('countries', 'longitude')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                });
            }
            if (! Schema::hasColumn('countries', 'is_active')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('longitude');
                });
            }
            if (! Schema::hasColumn('countries', 'is_eu_member')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->boolean('is_eu_member')->default(false)->after('is_active');
                });
            }
            if (! Schema::hasColumn('countries', 'display_order')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->integer('display_order')->default(999)->after('is_eu_member');
                });
            }
            if (! Schema::hasColumn('countries', 'metadata')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->json('metadata')->nullable()->after('display_order');
                });
            }
            if (! Schema::hasColumn('countries', 'created_at')) {
                Schema::table('countries', function (Blueprint $table) {
                    $table->timestamps();
                });
            }

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // manually drop tables or create drop logic if needed
    }
};
