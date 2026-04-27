type Translator = (key: string) => string;

type LocalizedNamedValue = {
    name_en?: string | null;
    name_am?: string | null;
};

const localizedHistoryNotes: Record<string, string> = {
    'Complaint submitted by complainant.': 'complaints.timeline.notes.submitted',
    'Complaint routed to the responsible department.': 'complaints.timeline.notes.assigned_to_department',
    'Department response recorded.': 'complaints.timeline.notes.department_response_recorded',
    'Complaint auto-escalated after deadline.': 'complaints.timeline.notes.auto_escalated',
};

const localizedEscalationReasons: Record<string, string> = {
    'Auto-escalated after the department response deadline expired.': 'complaints.escalation_types.auto_reason',
};

export function translateComplaintValue(prefix: string, value: string | null | undefined, t: Translator) {
    if (! value) {
        return '-';
    }

    const key = `${prefix}.${value}`;
    const translated = t(key);

    return translated === key ? humanize(value) : translated;
}

export function localizeName(value: LocalizedNamedValue | null | undefined, locale: string) {
    if (! value) {
        return '-';
    }

    if (locale === 'am') {
        return value.name_am ?? value.name_en ?? '-';
    }

    return value.name_en ?? value.name_am ?? '-';
}

export function translateComplaintHistoryAction(action: string | null | undefined, t: Translator) {
    return translateComplaintValue('complaints.timeline.actions', action, t);
}

export function translateComplaintHistoryNote(note: string | null | undefined, t: Translator) {
    if (! note || note.trim() === '') {
        return '-';
    }

    const key = localizedHistoryNotes[note.trim()];

    return key ? t(key) : note;
}

export function translateComplaintEscalationReason(reason: string | null | undefined, t: Translator) {
    if (! reason || reason.trim() === '') {
        return '-';
    }

    const key = localizedEscalationReasons[reason.trim()];

    return key ? t(key) : reason;
}

function humanize(value: string) {
    return value.replaceAll('_', ' ');
}
