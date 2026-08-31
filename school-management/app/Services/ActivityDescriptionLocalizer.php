<?php

namespace App\Services;

use Illuminate\Support\Carbon;

final class ActivityDescriptionLocalizer
{
    /**
     * Activity descriptions are deliberately stored as audit data and are not
     * rewritten when the interface language changes. This method translates
     * the known historical French formats only; unknown audit data is returned
     * byte-for-byte so that no information is lost.
     */
    public static function localize(?string $description): string
    {
        if ($description === null || $description === '') {
            return '';
        }

        $value = trim($description);

        foreach (self::rules() as [$pattern, $translationKey, $parameterNames]) {
            if (! preg_match($pattern, $value, $matches)) {
                continue;
            }

            $parameters = [];
            foreach ($parameterNames as $name) {
                $parameters[$name] = $matches[$name];
            }

            if (isset($parameters['month'])) {
                $parameters['month'] = self::localizeFrenchMonth($parameters['month']);
            }

            if (isset($parameters['fields'])) {
                $parameters['fields'] = self::localizeProfileFields($parameters['fields']);
            }

            if (isset($parameters['item'])) {
                $parameters['item'] = self::localizeTrashItem($parameters['item']);
            }

            return __($translationKey, $parameters);
        }

        return $description;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: array<int, string>}>
     */
    private static function rules(): array
    {
        $apostrophe = "[’']";

        return [
            ["/^s{$apostrophe}est connecté$/iu", 'Signed in', []],
            ["/^s{$apostrophe}est déconnecté$/iu", 'Signed out', []],
            ['/^a créé le programme (?<program>.+)$/iu', 'Created program :program', ['program']],
            ['/^a modifié le programme (?<program>.+)$/iu', 'Updated program :program', ['program']],
            ['/^a mis à jour le suivi\s*:\s*(?<lesson>.+)$/iu', 'Updated progress: :lesson', ['lesson']],
            ['/^a supprimé le programme (?<program>.+)$/iu', 'Deleted program :program', ['program']],
            ["/^a supprimé définitivement l{$apostrophe}élève (?<student>.+)$/iu", 'Permanently deleted student :student', ['student']],
            ['/^Paiement mensuel annulé\.$/iu', 'Monthly payment canceled.', []],
            ['/^Paiement mensuel validé\.$/iu', 'Monthly payment recorded.', []],
            ['/^Paiement mensuel annulé depuis les écolages\.$/iu', 'Monthly payment canceled from school fees.', []],
            ['/^Paiement mensuel validé depuis les écolages\.$/iu', 'Monthly payment recorded from school fees.', []],
            ["/^Droit d{$apostrophe}inscription payé\.$/iu", 'Registration fee paid.', []],
            ['/^Écolage en retard\s*:\s*(?<student>.+?)\s+[—–-]\s+(?<month>.+?)\.$/iu', 'School fee overdue: :student — :month.', ['student', 'month']],
            ['/^a transféré des élèves vers (?<class>.+)$/iu', 'Transferred students to :class', ['class']],
            ['/^a supprimé une classe et ses élèves inscrits\.$/iu', 'Deleted a class and its enrolled students.', []],
            ['/^a modifié ses informations de profil\s*:\s*(?<fields>.+)$/iu', 'Updated profile information: :fields', ['fields']],
            ['/^a restauré\s*:\s*(?<item>.+)$/iu', 'Restored: :item', ['item']],
            ['/^a supprimé définitivement\s*:\s*(?<item>.+)$/iu', 'Permanently deleted: :item', ['item']],
            ['/^Salaire payé\.$/iu', 'Salary paid.', []],
            ['/^a créé le compte (?<account>.+)$/iu', 'Created account :account', ['account']],
            ['/^a modifié le compte (?<account>.+)$/iu', 'Updated account :account', ['account']],
            ['/^a réactivé le compte (?<account>.+)$/iu', 'Reactivated account :account', ['account']],
            ['/^a suspendu le compte (?<account>.+)$/iu', 'Suspended account :account', ['account']],
            ['/^a réinitialisé le mot de passe du compte (?<account>.+)$/iu', 'Reset password for account :account', ['account']],
            ['/^a supprimé le compte (?<account>.+)$/iu', 'Deleted account :account', ['account']],
            ['/^Expense added:\s*(?<expense>.+)$/iu', 'Expense added: :expense', ['expense']],
        ];
    }

    private static function localizeProfileFields(string $fields): string
    {
        $labels = [
            'nom' => __('Name'),
            'e-mail' => __('Email'),
            'email' => __('Email'),
            'téléphone' => __('Phone'),
            'adresse' => __('Address'),
            'prénom' => __('First name'),
        ];

        return collect(preg_split('/\s*,\s*/u', $fields) ?: [])
            ->map(fn (string $field): string => $labels[mb_strtolower($field)] ?? $field)
            ->implode(', ');
    }

    private static function localizeTrashItem(string $item): string
    {
        $types = [
            'Programme' => 'Program',
            'Élève' => 'Student',
            'Classe' => 'Class',
            'Compte' => 'Account',
            'Historique de connexion' => 'Connection history',
        ];

        foreach ($types as $storedType => $translationKey) {
            if (preg_match('/^'.preg_quote($storedType, '/').'\s*:\s*(?<label>.+)$/iu', $item, $matches)) {
                return __($translationKey).': '.$matches['label'];
            }
        }

        if (preg_match('/^Notification\s*:\s*(?<description>.+)$/iu', $item, $matches)) {
            return __('Notification').': '.self::localize($matches['description']);
        }

        return $item;
    }

    private static function localizeFrenchMonth(string $month): string
    {
        $months = [
            'janvier' => 1,
            'février' => 2,
            'mars' => 3,
            'avril' => 4,
            'mai' => 5,
            'juin' => 6,
            'juillet' => 7,
            'août' => 8,
            'septembre' => 9,
            'octobre' => 10,
            'novembre' => 11,
            'décembre' => 12,
        ];

        if (! preg_match('/^(?<name>[[:alpha:]À-ÿ]+)\s+(?<year>\d{4})$/u', trim($month), $matches)) {
            return $month;
        }

        $number = $months[mb_strtolower($matches['name'])] ?? null;
        if ($number === null) {
            return $month;
        }

        return Carbon::create((int) $matches['year'], $number, 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F Y');
    }
}
