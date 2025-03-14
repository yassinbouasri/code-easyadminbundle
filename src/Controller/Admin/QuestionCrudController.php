<?php

namespace App\Controller\Admin;

use App\EasyAdmin\VotesField;
use App\Entity\Question;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class QuestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Question::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();
        yield Field::new('slug')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', $pageName !== Crud::PAGE_NEW);
        yield TextField::new('name');
        yield AssociationField::new('topic')
            ->autocomplete();
        yield TextAreaField::new('question')
            ->hideOnIndex()
            ->setFormTypeOptions([
                                     'row_attr' => [
                                         'data-controller' => 'snarkdown',
                                     ],
                                     'attr' => [
                                         'data-snarkdown-target' => 'input',
                                         'data-action' => 'snarkdown#render',
                                     ],
                                 ])
            ->setHelp('Preview:');
        yield VotesField::new('votes', 'Total votes')
            ->setTextAlign('center');
        yield AssociationField::new('askedBy')
            ->autocomplete()
            ->formatValue(static function ($value,Question $question) {
                if (!$user = $question->getAskedBy()) {
                    return null;
                }
                return sprintf('%s&nbsp;(%s)',$user->getEmail(), $user->getQuestions()->count());
            })
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                $queryBuilder->andWhere('entity.enabled = :enabled');
                $queryBuilder->setParameter('enabled', true);
            });
        yield AssociationField::new('answers')
            ->autocomplete()
            ->setFormType('by_reference', false);
        yield DateField::new('createdAt')
            ->onlyOnIndex();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort([
                'askedBy.enabled' => 'DESC',
                'createdAt' => 'DESC',
                             ]);
    }

}
