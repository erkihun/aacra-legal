<?php

declare(strict_types=1);

use Database\Seeders\DefaultLetterTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('creates the default request letter template once only', function (): void {
    $this->seed(DefaultLetterTemplateSeeder::class);
    $this->seed(DefaultLetterTemplateSeeder::class);

    $this->assertDatabaseCount('letter_templates', 1);
    $this->assertDatabaseHas('letter_templates', [
        'code' => 'DEFAULT-REQUEST-LETTER',
        'document_type' => 'request',
        'is_active' => true,
        'is_default' => true,
    ]);
});

it('updates an existing default template in place without changing its id or code', function (): void {
    $id = (string) Str::uuid7();

    DB::table('letter_templates')->insert([
        'id' => $id,
        'name' => 'Existing Admin Template',
        'code' => '001',
        'document_type' => null,
        'language' => 'am',
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'body_content' => '<p>Existing manually managed body.</p>',
        'is_active' => true,
        'is_default' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->seed(DefaultLetterTemplateSeeder::class);

    $this->assertDatabaseCount('letter_templates', 1);
    $this->assertDatabaseHas('letter_templates', [
        'id' => $id,
        'code' => '001',
        'name' => 'Existing Admin Template',
        'body_content' => '<p>Existing manually managed body.</p>',
        'document_type' => 'request',
        'is_active' => true,
        'is_default' => true,
    ]);
});
