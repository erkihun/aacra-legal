<?php

declare(strict_types=1);

use App\Enums\LegalCaseMainType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_cases', static function (Blueprint $table): void {
            $table->string('main_case_type')->nullable()->after('case_type_id')->index();
            $table->decimal('amount', 15, 2)->nullable()->after('institution_position');
            $table->string('crime_scene')->nullable()->after('amount');
            $table->string('police_station')->nullable()->after('crime_scene');
            $table->string('stolen_property_type')->nullable()->after('police_station');
            $table->decimal('stolen_property_estimated_value', 15, 2)->nullable()->after('stolen_property_type');
            $table->text('suspect_names')->nullable()->after('stolen_property_estimated_value');
            $table->date('statement_date')->nullable()->after('suspect_names');
        });

        DB::statement('ALTER TABLE legal_cases MODIFY court_id CHAR(36) NULL');
        DB::statement('ALTER TABLE legal_cases MODIFY case_type_id CHAR(36) NULL');
        DB::statement('ALTER TABLE legal_cases MODIFY plaintiff VARCHAR(255) NULL');
        DB::statement('ALTER TABLE legal_cases MODIFY defendant VARCHAR(255) NULL');

        DB::table('legal_cases')
            ->leftJoin('case_types', 'case_types.id', '=', 'legal_cases.case_type_id')
            ->whereNull('legal_cases.main_case_type')
            ->update([
                'legal_cases.main_case_type' => DB::raw("
                    CASE
                        WHEN UPPER(COALESCE(case_types.code, '')) = 'LAB' THEN '".LegalCaseMainType::LABOUR_DISPUTE->value."'
                        ELSE '".LegalCaseMainType::CIVIL_LAW->value."'
                    END
                "),
            ]);
    }

    public function down(): void
    {
        Schema::table('legal_cases', static function (Blueprint $table): void {
            $table->dropColumn([
                'main_case_type',
                'amount',
                'crime_scene',
                'police_station',
                'stolen_property_type',
                'stolen_property_estimated_value',
                'suspect_names',
                'statement_date',
            ]);
        });

        DB::statement('ALTER TABLE legal_cases MODIFY court_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE legal_cases MODIFY case_type_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE legal_cases MODIFY plaintiff VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE legal_cases MODIFY defendant VARCHAR(255) NOT NULL');
    }
};
