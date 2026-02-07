<?php

namespace App\Ai;

class GenreList
{
    public const GENRES = [
        'fr' => [
            'Science-Fiction', 'Fantastique', 'Policier', 'Thriller', 'Romance',
            'Roman historique', 'Aventure', 'Horreur', 'Humour', 'Classique',
            'Littérature jeunesse', 'Biographie', 'Essai', 'Poésie', 'Théâtre',
            'Contes et légendes', 'Dystopie', 'Space Opera', 'Philosophie',
            'Drame', 'Autobiographie', 'Nouvelle', 'Roman psychologique',
            'Théologie', 'Religion', 'Histoire', 'Voyage', 'Politique', 'Économie',
            'Art', 'Cuisine', 'Développement personnel', 'Guerre', 'Satire',
            'Bande dessinée', 'Manga', 'Érotique', 'Littérature générale',
        ],
        'en' => [
            'Science Fiction', 'Fantasy', 'Crime Fiction', 'Thriller', 'Romance',
            'Historical Fiction', 'Adventure', 'Horror', 'Humor', 'Classic',
            'Young Adult', 'Biography', 'Essay', 'Poetry', 'Drama',
            'Fairy Tales', 'Dystopia', 'Space Opera', 'Philosophy',
            'Literary Fiction', 'Autobiography', 'Short Stories', 'Psychological Fiction',
            'Theology', 'Religion', 'History', 'Travel', 'Politics', 'Economics',
            'Art', 'Cooking', 'Self-Help', 'War', 'Satire',
            'Comics', 'Manga', 'Erotica', 'General Fiction',
        ],
        'de' => [
            'Science-Fiction', 'Fantasy', 'Krimi', 'Thriller', 'Liebesroman',
            'Historischer Roman', 'Abenteuer', 'Horror', 'Humor', 'Klassiker',
            'Jugendbuch', 'Biografie', 'Essay', 'Lyrik', 'Drama',
            'Märchen', 'Dystopie', 'Space Opera', 'Philosophie',
            'Theologie', 'Religion', 'Geschichte', 'Reise', 'Politik', 'Wirtschaft',
            'Kunst', 'Kochen', 'Selbsthilfe', 'Krieg', 'Satire',
            'Comic', 'Manga', 'Erotik', 'Belletristik',
        ],
        'es' => [
            'Ciencia Ficción', 'Fantasía', 'Novela Negra', 'Suspense', 'Novela Romántica',
            'Novela Histórica', 'Aventura', 'Terror', 'Humor', 'Clásico',
            'Literatura Juvenil', 'Biografía', 'Ensayo', 'Poesía', 'Teatro',
            'Cuentos', 'Distopía', 'Space Opera', 'Filosofía',
            'Teología', 'Religión', 'Historia', 'Viajes', 'Política', 'Economía',
            'Arte', 'Cocina', 'Autoayuda', 'Guerra', 'Sátira',
            'Cómic', 'Manga', 'Erótica', 'Ficción general',
        ],
    ];

    /**
     * @return string[]
     */
    public static function getForLanguage(string $language): array
    {
        // Handle full language codes like "fr_FR" or "en_US"
        $shortLang = substr($language, 0, 2);

        return self::GENRES[$shortLang] ?? self::GENRES['en'];
    }
}
