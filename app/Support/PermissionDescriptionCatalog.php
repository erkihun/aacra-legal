<?php

declare(strict_types=1);

namespace App\Support;

final class PermissionDescriptionCatalog
{
    /**
     * @return array{en: string, am: string}
     */
    public static function resolve(string $permission): array
    {
        /** @var array<string, array{en?: string, am?: string}> $catalog */
        $catalog = config('permission_descriptions', []);
        $configured = $catalog[$permission] ?? null;

        if (is_array($configured) && filled($configured['en'] ?? null) && filled($configured['am'] ?? null)) {
            return [
                'en' => trim((string) $configured['en']),
                'am' => trim((string) $configured['am']),
            ];
        }

        return [
            'en' => self::buildEnglishFallback($permission),
            'am' => self::buildAmharicFallback($permission),
        ];
    }

    private static function buildEnglishFallback(string $permission): string
    {
        [$resource, $action] = self::splitPermission($permission);

        return sprintf(
            'Allows the user to %s %s.',
            self::englishAction($action),
            self::englishResource($resource),
        );
    }

    private static function buildAmharicFallback(string $permission): string
    {
        [$resource, $action] = self::splitPermission($permission);

        return sprintf(
            'ተጠቃሚው %s %s ያስችለዋል።',
            self::amharicResource($resource),
            self::amharicAction($action),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitPermission(string $permission): array
    {
        if (! str_contains($permission, '.')) {
            return [$permission, 'manage'];
        }

        $parts = explode('.', $permission);
        $action = array_pop($parts);

        return [implode('.', $parts), $action ?: 'manage'];
    }

    private static function englishAction(string $action): string
    {
        return match ($action) {
            'view' => 'view',
            'view_any' => 'view all',
            'view_own' => 'view their own',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            'manage' => 'manage',
            'preview' => 'preview',
            'print' => 'print',
            'approve' => 'approve',
            'review' => 'review',
            'assign' => 'assign',
            'assign_roles' => 'assign roles for',
            'assign_team_leader' => 'assign team leaders for',
            'assign_expert' => 'assign legal experts for',
            'respond' => 'record responses for',
            'respond_department' => 'record department responses for',
            'forward_to_committee' => 'forward',
            'decide' => 'record decisions for',
            'comment' => 'add internal comments to',
            'attach' => 'attach files to',
            'attachments' => 'manage attachments for',
            'record_hearing' => 'record hearing details for',
            'close' => 'close',
            'reopen' => 'reopen',
            'submit' => 'submit',
            'export' => 'export',
            default => str($action)->replace(['_', '-'], ' ')->lower()->toString(),
        };
    }

    private static function englishResource(string $resource): string
    {
        return match ($resource) {
            'dashboard' => 'the dashboard',
            'users' => 'user accounts',
            'roles' => 'roles',
            'permissions' => 'permissions',
            'departments' => 'departments',
            'branches' => 'branches',
            'teams' => 'teams',
            'advisory' => 'advisory records',
            'advisory-categories' => 'advisory categories',
            'advisory-requests' => 'advisory requests',
            'cases' => 'court cases',
            'legal-cases' => 'legal cases',
            'legal-case-types' => 'legal case types',
            'case-reopen' => 'closed court cases through the legacy reopen path',
            'complaints' => 'complaint records',
            'complaint-categories' => 'complaint categories',
            'letters' => 'letters',
            'letter_templates' => 'letter templates',
            'attachments' => 'record attachments',
            'comments' => 'internal comments',
            'reports' => 'reports',
            'settings' => 'system settings',
            'references' => 'reference data',
            'audit' => 'audit summaries',
            'audit-logs' => 'audit logs',
            'public-posts' => 'public posts',
            'courts' => 'court records',
            default => str($resource)->replace(['_', '-', '.'], ' ')->lower()->toString(),
        };
    }

    private static function amharicAction(string $action): string
    {
        return match ($action) {
            'view' => 'እንዲያይ',
            'view_any' => 'ሁሉንም እንዲያይ',
            'view_own' => 'የራሱን እንዲያይ',
            'create' => 'እንዲፈጥር',
            'update' => 'እንዲያሻሽል',
            'delete' => 'እንዲሰርዝ',
            'manage' => 'እንዲያስተዳድር',
            'preview' => 'እንዲቀይቅ',
            'print' => 'እንዲያትም',
            'approve' => 'እንዲያጸድቅ',
            'review' => 'እንዲገምግም',
            'assign' => 'እንዲመድብ',
            'assign_roles' => 'ሚና እንዲመድብ',
            'assign_team_leader' => 'ለቡድን መሪ እንዲመድብ',
            'assign_expert' => 'ለህግ ባለሙያ እንዲመድብ',
            'respond' => 'ምላሽ እንዲመዘግብ',
            'respond_department' => 'የመምሪያ ምላሽ እንዲመዘግብ',
            'forward_to_committee' => 'ወደ ኮሚቴ እንዲያስተላልፍ',
            'decide' => 'ውሳኔ እንዲመዘግብ',
            'comment' => 'የውስጥ አስተያየት እንዲያክል',
            'attach' => 'ፋይል እንዲያያዝ',
            'attachments' => 'አባሪዎችን እንዲያስተዳድር',
            'record_hearing' => 'የችሎት መረጃ እንዲመዘግብ',
            'close' => 'እንዲዘጋ',
            'reopen' => 'እንደገና እንዲከፍት',
            'submit' => 'እንዲያቀርብ',
            'export' => 'ወደ ፋይል እንዲያወጣ',
            default => 'እንዲያስፈጽም',
        };
    }

    private static function amharicResource(string $resource): string
    {
        return match ($resource) {
            'dashboard' => 'ዳሽቦርዱን',
            'users' => 'የተጠቃሚ መለያዎችን',
            'roles' => 'ሚናዎችን',
            'permissions' => 'ፈቃዶችን',
            'departments' => 'የሥራ ክፍሎችን',
            'branches' => 'ቅርንጫፎችን',
            'teams' => 'ቡድኖችን',
            'advisory' => 'የህግ ምክር መዝገቦችን',
            'advisory-categories' => 'የህግ ምክር ምድቦችን',
            'advisory-requests' => 'የህግ ምክር ጥያቄዎችን',
            'cases' => 'የፍርድ ጉዳዮችን',
            'legal-cases' => 'የፍርድ ጉዳይ መዝገቦችን',
            'legal-case-types' => 'የፍርድ ጉዳይ አይነቶችን',
            'case-reopen' => 'በነባር መንገድ የተዘጉ የፍርድ ጉዳዮችን',
            'complaints' => 'የቅሬታ መዝገቦችን',
            'complaint-categories' => 'የቅሬታ ምድቦችን',
            'letters' => 'ደብዳቤዎችን',
            'letter_templates' => 'የደብዳቤ አብነቶችን',
            'attachments' => 'አባሪ ፋይሎችን',
            'comments' => 'የውስጥ አስተያየቶችን',
            'reports' => 'ሪፖርቶችን',
            'settings' => 'የስርዓት ቅንብሮችን',
            'references' => 'መሠረታዊ መረጃዎችን',
            'audit' => 'የኦዲት ማጠቃለያዎችን',
            'audit-logs' => 'የኦዲት መዝገቦችን',
            'public-posts' => 'የህዝብ ልጥፎችን',
            'courts' => 'የፍርድ ቤት መዝገቦችን',
            default => str($resource)->replace(['_', '-', '.'], ' ')->toString(),
        };
    }
}
