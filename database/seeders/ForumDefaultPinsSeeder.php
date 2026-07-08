<?php

namespace Database\Seeders;

use App\Models\Forum\ForumTopic;
use App\Models\User;
use Illuminate\Database\Seeder;

class ForumDefaultPinsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();

        if (!$admin) {
            $this->command->warn('Aucun utilisateur trouvé. Créez un compte admin avant de lancer ce seeder.');
            return;
        }

        $topics = [
            [
                'title'   => 'Règles générales du forum',
                'content' => "Bienvenue sur le forum de NAKAMANET !\n\nPour que cet espace reste agréable pour tout le monde, merci de respecter ces règles :\n\n1. Soyez respectueux envers les autres membres.\n2. Pas de harcèlement, insultes ou propos discriminatoires.\n3. Restez dans le sujet de la catégorie choisie.\n4. Pas de spam ni de publicité non sollicitée.\n5. Les spoilers doivent être postés dans la catégorie Spoilers.\n6. Le non-respect de ces règles entraîne un avertissement ou un bannissement.",
            ],
            [
                'title'   => 'Guide de bonne conduite',
                'content' => "Pour contribuer positivement à la communauté :\n\n- Formulez vos critiques de manière constructive.\n- Citez vos sources lorsque vous partagez des informations.\n- Évitez les doublons : cherchez avant de poster un nouveau sujet.\n- Utilisez la fonction de vote pour valoriser les contributions utiles.\n- Répondez aux sujets existants plutôt que d'en créer de nouveaux similaires.",
            ],
            [
                'title'   => 'Comment signaler un contenu inapproprié',
                'content' => "Si vous constatez un contenu qui enfreint les règles :\n\n1. Contactez un modérateur via messagerie privée.\n2. Indiquez le lien vers le sujet ou la réponse concernée.\n3. Décrivez brièvement le problème constaté.\n\nNous traitons toutes les signalements dans les plus brefs délais.\nMerci de ne pas répondre au contenu problématique — ignorez-le et signalez-le.",
            ],
        ];

        foreach ($topics as $data) {
            ForumTopic::firstOrCreate(
                ['title' => $data['title']],
                [
                    'content'  => $data['content'],
                    'category' => 'general',
                    'user_id'  => $admin->id,
                    'is_pinned' => true,
                    'is_locked' => true,
                ]
            );
        }

        $this->command->info('Sujets épinglés par défaut créés avec succès.');
    }
}
