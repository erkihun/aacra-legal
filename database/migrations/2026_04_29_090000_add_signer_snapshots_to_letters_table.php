<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', static function (Blueprint $table): void {
            if (! Schema::hasColumn('letters', 'signature_image_path_snapshot')) {
                $table->string('signature_image_path_snapshot')->nullable()->after('footer_image_path_snapshot');
            }

            if (! Schema::hasColumn('letters', 'signer_full_name_snapshot')) {
                $table->string('signer_full_name_snapshot')->nullable()->after('signature_image_path_snapshot');
            }

            if (! Schema::hasColumn('letters', 'signer_title_snapshot')) {
                $table->string('signer_title_snapshot')->nullable()->after('signer_full_name_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('letters', static function (Blueprint $table): void {
            foreach ([
                'signature_image_path_snapshot',
                'signer_full_name_snapshot',
                'signer_title_snapshot',
            ] as $column) {
                if (Schema::hasColumn('letters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
