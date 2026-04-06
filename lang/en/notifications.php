<?php

declare(strict_types=1);

return [
    'feed' => [
        'types' => [
            'general' => 'General notification',
            'advisory_assigned' => 'Advisory assignment',
            'advisory_response_recorded' => 'Advisory response',
            'advisory_overdue' => 'Overdue advisory request',
            'case_assigned' => 'Case assignment',
            'case_upcoming_hearing' => 'Upcoming hearing',
            'case_appeal_deadline' => 'Appeal deadline',
        ],
        'titles' => [
            'advisory_assigned' => 'Advisory request assigned',
            'advisory_response_recorded' => 'Advisory response received',
            'advisory_overdue' => 'Overdue advisory request',
            'case_assigned' => 'Legal case assigned',
            'case_upcoming_hearing' => 'Upcoming hearing reminder',
            'case_appeal_deadline' => 'Appeal deadline reminder',
        ],
        'messages' => [
            'advisory_assigned' => ':assigned_by assigned a new advisory request for ":subject".',
            'advisory_response_recorded' => ':responder_name responded to your advisory request ":subject" on :responded_at.',
            'advisory_overdue' => 'This advisory request is overdue. Due date: :due_date.',
            'case_assigned' => ':assigned_by assigned a legal case to your workspace.',
            'case_upcoming_hearing' => 'A hearing is approaching. Next hearing date: :next_hearing_date.',
            'case_appeal_deadline' => 'An appeal deadline is approaching. Deadline: :appeal_deadline.',
        ],
    ],
];
