<?php

declare(strict_types=1);

it('uses the ethiopian calendar widget for amharic locale and keeps the english native input path', function (): void {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/Components/Ui/LocalizedDateInput.tsx');

    expect($source)
        ->toBeString()
        ->toContain("import { EtCalendar } from 'react-ethiopian-calendar';")
        ->toContain("if (locale === 'am')")
        ->toContain('calendarType={true}')
        ->toContain('lang="am"')
        ->toContain('type="date"');
});
