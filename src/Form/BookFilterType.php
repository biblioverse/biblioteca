<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\User;
use App\Enum\AgeCategory;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class BookFilterType extends AbstractType
{
    public const AUTOCOMPLETE_DELIMITER = '🪓';

    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod(Request::METHOD_GET);

        $builder->add('title', SearchType::class, [
            'required' => false,
            'label' => 'filter.title',
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue !== null) {
                    $qb->andWhere($qb->expr()->like('book.title', ':title'));
                    $qb->setParameter('title', '%'.$searchValue.'%');
                }
            },
        ]);

        $builder->add('serieIndexGTE', SearchType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'filter.indexgte',
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue !== null) {
                    $qb->andWhere('book.serieIndex >= :indexGTE');
                    $qb->setParameter('indexGTE', $searchValue);
                }
            },
        ]);

        $builder->add('serieIndexLTE', SearchType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'filter.indexlte',
            'target_callback' => function (QueryBuilder $qb, ?string $searchValue): void {
                if ($searchValue !== null) {
                    $qb->andWhere('book.serieIndex <= :indexLTE or book.serieIndex is null');
                    $qb->setParameter('indexLTE', $searchValue);
                }
            },
        ]);

        $builder->add('authors', TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'label' => 'filter.authors',
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

        $builder->add('authorsNot', TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'label' => 'filter.authornot',
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

        $builder->add('tags', TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'label' => 'filter.tags',
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

        $builder->add('serie', TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'label' => 'filter.serie',
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

        $builder->add('publisher', TextType::class, [
            'autocomplete' => true,
            'tom_select_options' => [
                'create' => false,
                'delimiter' => self::AUTOCOMPLETE_DELIMITER,
            ],
            'mapped' => false,
            'required' => false,
            'label' => 'filter.publisher',
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

        $builder->add('read', EnumType::class, [
            'required' => false,
            'class' => ReadStatus::class,
            'label' => 'filter.read',
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?ReadStatus $readValue): void {
                if ($readValue !== null) {
                    $qb->andWhere('bookInteraction.readStatus = :readstatus');
                    $qb->setParameter('readstatus', $readValue);
                }
            },
        ]);

        $builder->add('extension', ChoiceType::class, [
            'choices' => [
                'Any' => '',
                'epub' => 'epub',
                'cbr' => 'cbr',
                'cbz' => 'cbz',
                'pdf' => 'pdf',
            ],
            'label' => 'filter.extension',
            'required' => false,
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $readValue): void {
                if ($readValue !== null && $readValue !== '') {
                    $qb->andWhere($qb->expr()->like('book.extension', ':extension'));
                    $qb->setParameter('extension', $readValue);
                }
            },
        ]);

        $builder->add('age', EnumType::class, [
            'class' => AgeCategory::class,
            'required' => false,
            'mapped' => false,
            'expanded' => false,
            'label' => 'filter.age',
            'multiple' => false,
            'target_callback' => function (QueryBuilder $qb, ?AgeCategory $readValue): void {

                if ($readValue !== null) {
                    $qb->andWhere($qb->expr()->in('book.ageCategory', ':ageCategory'));
                    $qb->setParameter('ageCategory', $readValue);
                }
            },
        ]);

        $builder->add('favorite', EnumType::class, [
            'class' => ReadingList::class,
            'required' => false,
            'label' => 'filter.favorite',
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?ReadingList $readValue): void {

                if ($readValue !== null) {
                    $qb->andWhere('bookInteraction.readingList = :readinglist');
                    $qb->setParameter('readinglist', $readValue);
                }
            },
        ]);

        $builder->add('verified', ChoiceType::class, [
            'choices' => [
                'filter.verified.any' => '',
                'filter.verified.yes' => 'verified',
                'filter.verified.no' => 'unverified',
            ],
            'required' => false,
            'label' => 'filter.verified',
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

        $builder->add('picture', ChoiceType::class, [
            'choices' => [
                'filter.picture.any' => '',
                'filter.picture.yes' => 'with',
                'filter.picture.no' => 'without',
            ],
            'required' => false,
            'label' => 'filter.picture',
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

        $builder->add('orderBy', ChoiceType::class, [
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
            'label' => 'filter.orderby',
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $orderByValue): void {
                $direction = 'ASC';
                if ($orderByValue === null) {
                    $params = $qb->getParameters()->toArray();
                    $params = array_filter($params, static fn ($param) => $param->getName() === 'serie0');
                    if ($params !== []) {
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

        $builder->add('displayMode', HiddenType::class, [
            'data' => 'list',
            'mapped' => false,
            'target_callback' => function (QueryBuilder $qb, ?string $orderByValue): void {
            },
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'filter.submit',
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
