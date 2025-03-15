<?php

namespace App\Controller\Admin;

use App\EasyAdmin\VotesField;
use App\Entity\Answer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AnswerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Answer::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
                     ->onlyOnIndex();
        yield Field::new('answer')
            ->setSortable(false);
        yield VotesField::new('votes');
        yield AssociationField::new('question')
            ->autocomplete()
            ->setCrudController(QuestionCrudController::class)
            ->hideOnIndex();
        yield AssociationField::new('answeredBy');
        yield Field::new('createdAt')
                   ->hideOnForm();
        yield Field::new('updatedAt')
                   ->onlyOnDetail();

    }
}
