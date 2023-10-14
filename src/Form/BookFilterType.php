<?php

namespace App\Form;

use App\Entity\Book;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class BookFilterType extends AbstractType
{
    public const AUTOCOMPLETE_DELIMITER = '🪓';

    public function __construct(private RouterInterface $router)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod('GET');

        $builder->add('title', Type\SearchType::class, [
            'required' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue !== null) {
                    $qb->andWhere($qb->expr()->like('book.title', ':title'));
                    $qb->setParameter('title', '%'.$searchValue.'%');
                }
            },
        ]);

        $builder->add('serieIndexGTE', Type\SearchType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'Index >=',
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue !== null) {
                    $qb->andWhere('book.serieIndex >= :indexGTE');
                    $qb->setParameter('indexGTE', $searchValue);
                }
            },
        ]);

        $builder->add('serieIndexLTE', Type\SearchType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'Index <=',
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue !== null) {
                    $qb->andWhere('book.serieIndex <= :indexLTE or book.serieIndex is null');
                    $qb->setParameter('indexLTE', $searchValue);
                }
            },
        ]);

        $builder->add('authors', Type\TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'authors', 'create' => false]),
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue === null || $searchValue === '') {
                    return;
                }
                $authors = explode(self::AUTOCOMPLETE_DELIMITER, $searchValue);

                $orModule = $qb->expr()->orX();

                foreach ($authors as $key => $author) {
                    $orModule->add('JSON_CONTAINS(lower(book.authors), :author'.$key.')=1');
                    $qb->setParameter('author'.$key, json_encode([strtolower($author)]));
                }
                $qb->andWhere($orModule);
            },
        ]);

        $builder->add('authorsNot', Type\TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'label' => 'Author not in',
            'required' => false,
            'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'authors', 'create' => false]),
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue === null || $searchValue === '') {
                    return;
                }
                $authors = explode(self::AUTOCOMPLETE_DELIMITER, $searchValue);

                $orModule = $qb->expr()->orX();

                foreach ($authors as $key => $author) {
                    $orModule->add('JSON_CONTAINS(lower(book.authors), :authorNot'.$key.')=0');
                    $qb->setParameter('authorNot'.$key, json_encode([strtolower($author)]));
                }
                $qb->andWhere($orModule);
            },
        ]);

        $builder->add('tags', Type\TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'tags', 'create' => false]),
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue === null || $searchValue === '') {
                    return;
                }
                $tags = explode(self::AUTOCOMPLETE_DELIMITER, $searchValue);

                $orModule = $qb->expr()->orX();

                foreach ($tags as $key => $tag) {
                    if ($tag === 'no_tags') {
                        $orModule->add('book.tags = \'[]\'');
                    } else {
                        $orModule->add('JSON_CONTAINS(lower(book.tags), :tag'.$key.')=1');
                        $qb->setParameter('tag'.$key, json_encode([strtolower($tag)]));
                    }
                }
                $qb->andWhere($orModule);
            },
        ]);

        $builder->add('serie', Type\TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'serie', 'create' => false]),
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue === null || $searchValue === '') {
                    return;
                }
                $series = explode(self::AUTOCOMPLETE_DELIMITER, $searchValue);

                $orModule = $qb->expr()->orX();

                foreach ($series as $key => $serie) {
                    if ($serie === 'no_serie') {
                        $orModule->add('book.serie is null');
                    } else {
                        $orModule->add('book.serie=:serie'.$key);
                        $qb->setParameter('serie'.$key, $serie);
                    }
                }
                $qb->andWhere($orModule);
            },
        ]);

        $builder->add('publisher', Type\TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'publisher', 'create' => false]),
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue === null || $searchValue === '') {
                    return;
                }
                $publishers = explode(self::AUTOCOMPLETE_DELIMITER, $searchValue);

                $orModule = $qb->expr()->orX();

                foreach ($publishers as $key => $publisher) {
                    if ($publisher === 'no_publisher') {
                        $orModule->add('book.publisher = \'[]\'');
                    } else {
                        $orModule->add('book.publisher=:publisher'.$key);
                        $qb->setParameter('publisher'.$key, $publisher);
                    }
                }
                $qb->andWhere($orModule);
            },
        ]);

        $builder->add('read', Type\ChoiceType::class, [
            'choices' => [
                'Any' => '',
                'Read' => 'read',
                'Unread' => 'unread',
            ],
            'required' => false,
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $readValue): void {
                switch ($readValue) {
                    case 'read':
                        $qb->andWhere('bookInteraction.finished = true');
                        break;
                    case 'unread':
                        $qb->andWhere('(bookInteraction.finished = false OR bookInteraction.finished IS NULL)');
                        break;
                }
            },
        ]);

        $builder->add('extension', Type\ChoiceType::class, [
            'choices' => [
                'Any' => '',
                'epub' => 'epub',
                'cbr' => 'cbr',
                'cbz' => 'cbz',
                'pdf' => 'pdf',
            ],
            'required' => false,
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $readValue): void {
                if ($readValue !== null && $readValue !== '') {
                    $qb->andWhere($qb->expr()->like('book.extension', ':extension'));
                    $qb->setParameter('extension', $readValue);
                }
            },
        ]);

        $builder->add('favorite', Type\ChoiceType::class, [
            'choices' => [
                'Any' => '',
                'Favorite' => 'favorite',
                'Not favorite' => 'notfavorite',
            ],
            'required' => false,
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $readValue): void {
                switch ($readValue) {
                    case 'favorite':
                        $qb->andWhere('bookInteraction.favorite = true');
                        break;
                    case 'notfavorite':
                        $qb->andWhere('(bookInteraction.favorite = false OR bookInteraction.favorite IS NULL)');
                        break;
                }
            },
        ]);

        $builder->add('verified', Type\ChoiceType::class, [
            'choices' => [
                'Any' => '',
                'Verified' => 'verified',
                'Not Verified' => 'unverified',
            ],
            'required' => false,
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $readValue): void {
                switch ($readValue) {
                    case 'verified':
                        $qb->andWhere('book.verified = true');
                        break;
                    case 'unverified':
                        $qb->andWhere('book.verified = false');
                        break;
                }
            },
        ]);

        $builder->add('picture', Type\ChoiceType::class, [
            'choices' => [
                'Any' => '',
                'Has picture' => 'with',
                'No picture' => 'without',
            ],
            'required' => false,
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $readValue): void {
                switch ($readValue) {
                    case 'with':
                        $qb->andWhere('book.imageFilename is not null');
                        break;
                    case 'without':
                        $qb->andWhere('book.imageFilename is null');
                        break;
                }
            },
        ]);

        $builder->add('orderBy', Type\ChoiceType::class, [
            'choices' => [
                'created (ASC)' => 'created-asc',
                'created (DESC)' => 'created-desc',
                'title (ASC)' => 'title-asc',
                'title (DESC)' => 'title-desc',
                'updated (ASC)' => 'updated-asc',
                'updated (DESC)' => 'updated-desc',
                'id (ASC)' => 'id-asc',
                'id (DESC)' => 'id-desc',
                'serieIndex (ASC)' => 'serieIndex-asc',
                'serieIndex (DESC)' => 'serieIndex-desc',
            ],
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $orderByValue): void {
                $direction = 'ASC';
                if ($orderByValue === null) {
                    $params = $qb->getParameters()->toArray();
                    $params = array_filter($params, static function ($param) {
                        return $param->getName() === 'serie0';
                    });
                    if (count($params) > 0) {
                        $orderByValue = 'serieIndex';
                    } else {
                        $orderByValue = 'created';
                        $direction = 'DESC';
                    }
                } else {
                    [$orderByValue, $direction] = explode('-', $orderByValue);
                }

                $qb->orderBy('book.'.$orderByValue, $direction);
                $qb->addOrderBy('book.serieIndex', 'ASC');
                $qb->addOrderBy('book.id', 'ASC');
            },
        ]);

        $builder->add('displayMode', Type\HiddenType::class, [
            'data' => 'list',
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $orderByValue): void {
            },
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => 'Filter',
            'attr' => [
                'class' => 'btn btn-primary',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
            'csrf_protection' => false,
        ]);
    }
}
