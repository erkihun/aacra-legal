<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'complainant_city',
            'complainant_sub_city',
            'complainant_woreda',
            'complainant_house_number',
            'complaint_essence',
            'incident_date',
            'incident_sub_city',
            'incident_woreda',
            'concerned_employee_name',
            'requested_resolution',
            'evidence_note',
        ];

        $missingColumns = array_filter(
            $columns,
            static fn (string $column): bool => ! Schema::hasColumn('complaints', $column),
        );

        if ($missingColumns === []) {
            return;
        }

        Schema::table('complaints', static function (Blueprint $table): void {
            if (! Schema::hasColumn('complaints', 'complainant_city')) {
                $table->string('complainant_city')->nullable()->after('complainant_phone');
            }

            if (! Schema::hasColumn('complaints', 'complainant_sub_city')) {
                $table->string('complainant_sub_city')->nullable()->after('complainant_city');
            }

            if (! Schema::hasColumn('complaints', 'complainant_woreda')) {
                $table->string('complainant_woreda')->nullable()->after('complainant_sub_city');
            }

            if (! Schema::hasColumn('complaints', 'complainant_house_number')) {
                $table->string('complainant_house_number')->nullable()->after('complainant_woreda');
            }

            if (! Schema::hasColumn('complaints', 'complaint_essence')) {
                $table->longText('complaint_essence')->nullable()->after('subject');
            }

            if (! Schema::hasColumn('complaints', 'incident_date')) {
                $table->date('incident_date')->nullable()->after('priority');
            }

            if (! Schema::hasColumn('complaints', 'incident_sub_city')) {
                $table->string('incident_sub_city')->nullable()->after('incident_date');
            }

            if (! Schema::hasColumn('complaints', 'incident_woreda')) {
                $table->string('incident_woreda')->nullable()->after('incident_sub_city');
            }

            if (! Schema::hasColumn('complaints', 'concerned_employee_name')) {
                $table->string('concerned_employee_name')->nullable()->after('department_id');
            }

            if (! Schema::hasColumn('complaints', 'requested_resolution')) {
                $table->text('requested_resolution')->nullable()->after('details');
            }

            if (! Schema::hasColumn('complaints', 'evidence_note')) {
                $table->text('evidence_note')->nullable()->after('requested_resolution');
            }
        });
    }

    public function down(): void
    {
        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('complaints', 'complainant_city') ? 'complainant_city' : null,
            Schema::hasColumn('complaints', 'complainant_sub_city') ? 'complainant_sub_city' : null,
            Schema::hasColumn('complaints', 'complainant_woreda') ? 'complainant_woreda' : null,
            Schema::hasColumn('complaints', 'complainant_house_number') ? 'complainant_house_number' : null,
            Schema::hasColumn('complaints', 'complaint_essence') ? 'complaint_essence' : null,
            Schema::hasColumn('complaints', 'incident_date') ? 'incident_date' : null,
            Schema::hasColumn('complaints', 'incident_sub_city') ? 'incident_sub_city' : null,
            Schema::hasColumn('complaints', 'incident_woreda') ? 'incident_woreda' : null,
            Schema::hasColumn('complaints', 'concerned_employee_name') ? 'concerned_employee_name' : null,
            Schema::hasColumn('complaints', 'requested_resolution') ? 'requested_resolution' : null,
            Schema::hasColumn('complaints', 'evidence_note') ? 'evidence_note' : null,
        ]));

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('complaints', static function (Blueprint $table): void {
            $table->dropColumn($columnsToDrop);
        });
    }
};
