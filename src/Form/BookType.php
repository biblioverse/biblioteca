<?php

namespace App\Form;

use App\Entity\Book;
use App\Enum\AgeCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class BookType extends AbstractType
{
    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => true])
            ->add('summary', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('serie', TextType::class, [
                'required' => false,
                'autocomplete' => true,
                'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'serie']),
            ])
            ->add('serieIndex', NumberType::class, [
                'required' => false,
            ])
            ->add('language', ChoiceType::class, [
                'choices' => $this->getTwoLettersLocales(),
                'translation_domain' => false,
                'required' => false,
            ])
            ->add('pageNumber', NumberType::class, [
                'required' => false,
            ])
            ->add('publisher', TextType::class, [
                'autocomplete' => true,
                'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'publisher']),
                'required' => false,
            ])
            ->add('publishDate', null, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('authorsString', TextType::class, [
                'required' => true,
                'autocomplete' => true,
                'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'authors']),
            ])
            ->add('tagsString', TextType::class, [
                'required' => false,
                'autocomplete' => true,
                'autocomplete_url' => $this->router->generate('app_autocomplete_group', ['type' => 'tags']),
            ])
            ->add('verified', CheckboxType::class, [
                'required' => false,
            ])
            ->add('ageCategory', EnumType::class, [
                'class' => AgeCategory::class,
                'required' => false,
            ])
        ;
    }

    /**
     * @return array<string,string> list of languages indexed by their 2-letter locale code
     */
    private function getTwoLettersLocales(): array
    {
        // Currently only 2-letter locales are supported in DB field.
        return array_flip(array_filter(Locales::getNames(), fn (string $locale) => strlen($locale) === 2, ARRAY_FILTER_USE_KEY));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
            'label_translation_prefix' => 'book.form.',
        ]);
    }
}
