<?php

declare(strict_types=1);

return [
    'feed' => [
        'types' => [
            'general' => 'አጠቃላይ ማሳወቂያ',
            'advisory_assigned' => 'የሕግ ምክር ጥያቄ ምደባ',
            'advisory_overdue' => 'የዘገየ የሕግ ምክር ጥያቄ',
            'case_assigned' => 'የፍርድ ቤት ጉዳይ ምደባ',
            'case_upcoming_hearing' => 'ቀጣይ ችሎት',
            'case_appeal_deadline' => 'የይግባኝ ጊዜ ገደብ',
        ],
        'titles' => [
            'advisory_assigned' => 'የሕግ ምክር ጥያቄ ተመድቧል',
            'advisory_overdue' => 'የሕግ ምክር ጥያቄ ዘግይቷል',
            'case_assigned' => 'የፍርድ ቤት ጉዳይ ተመድቧል',
            'case_upcoming_hearing' => 'የቀጣይ ችሎት ማሳወቂያ',
            'case_appeal_deadline' => 'የይግባኝ ጊዜ ገደብ ማሳወቂያ',
        ],
        'messages' => [
            'advisory_assigned' => ':assigned_by የ":subject" የሕግ ምክር ጥያቄን ለእርስዎ መድቧል።',
            'advisory_overdue' => 'የ":subject" የሕግ ምክር ጥያቄ ከየመጨረሻ ቀኑ አልፏል። የመጨረሻ ቀን: :due_date።',
            'case_assigned' => ':assigned_by የፍርድ ቤት ጉዳዩን ለእርስዎ መድቧል።',
            'case_upcoming_hearing' => 'ለዚህ ጉዳይ ቀጣይ ችሎት ተይዟል። የችሎቱ ቀን: :next_hearing_date።',
            'case_appeal_deadline' => 'የይግባኝ ማቅረቢያ ጊዜ ገደብ በቅርቡ ይደርሳል። የመጨረሻ ቀን: :appeal_deadline።',
        ],
    ],
];
