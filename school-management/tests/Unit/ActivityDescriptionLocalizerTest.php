<?php

namespace Tests\Unit;

use App\Services\ActivityDescriptionLocalizer;
use Tests\TestCase;

class ActivityDescriptionLocalizerTest extends TestCase
{
    public function test_it_localizes_every_current_activity_description_format(): void
    {
        app()->setLocale('en');

        $examples = [
            's’est connecté' => 'Signed in',
            's’est déconnecté' => 'Signed out',
            'a créé le programme Français A1' => 'Created program Français A1',
            'a modifié le programme Français A1' => 'Updated program Français A1',
            'a mis à jour le suivi : Chapitre 2' => 'Updated progress: Chapitre 2',
            'a supprimé le programme Français A1' => 'Deleted program Français A1',
            'a supprimé définitivement l’élève Jean Dupont' => 'Permanently deleted student Jean Dupont',
            'Paiement mensuel annulé.' => 'Monthly payment canceled.',
            'Paiement mensuel validé.' => 'Monthly payment recorded.',
            'Paiement mensuel annulé depuis les écolages.' => 'Monthly payment canceled from school fees.',
            'Paiement mensuel validé depuis les écolages.' => 'Monthly payment recorded from school fees.',
            'Droit d’inscription payé.' => 'Registration fee paid.',
            'Écolage en retard : Jean Dupont — août 2026.' => 'School fee overdue: Jean Dupont — August 2026.',
            'a transféré des élèves vers A2' => 'Transferred students to A2',
            'a supprimé une classe et ses élèves inscrits.' => 'Deleted a class and its enrolled students.',
            'a modifié ses informations de profil : prénom, téléphone, e-mail' => 'Updated profile information: First name, Phone, Email',
            'a restauré : Programme : Français A1' => 'Restored: Program: Français A1',
            'a supprimé définitivement : Compte : Jean Dupont' => 'Permanently deleted: Account: Jean Dupont',
            'Salaire payé.' => 'Salary paid.',
            'a créé le compte Jean Dupont' => 'Created account Jean Dupont',
            'a modifié le compte Jean Dupont' => 'Updated account Jean Dupont',
            'a réactivé le compte Jean Dupont' => 'Reactivated account Jean Dupont',
            'a suspendu le compte Jean Dupont' => 'Suspended account Jean Dupont',
            'a réinitialisé le mot de passe du compte Jean Dupont' => 'Reset password for account Jean Dupont',
            'a supprimé le compte Jean Dupont' => 'Deleted account Jean Dupont',
        ];

        foreach ($examples as $description => $expected) {
            $this->assertSame($expected, ActivityDescriptionLocalizer::localize($description), $description);
        }
    }

    public function test_it_preserves_unknown_activity_descriptions_exactly(): void
    {
        $description = "  Description personnalisée inconnue.  ";

        $this->assertSame($description, ActivityDescriptionLocalizer::localize($description));
    }
}
