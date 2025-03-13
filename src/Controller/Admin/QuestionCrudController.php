<?php

namespace App\Controller\Admin;

use App\Entity\Question;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
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
        yield TextField::new('name');
        yield AssociationField::new('topic')
            ->autocomplete();
        yield TextAreaField::new('question');
        yield Field::new('votes', 'Total votes')
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
        yield DateField::new('createdAt')
            ->onlyOnIndex();
    }

}
