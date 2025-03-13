<?php

namespace App\Controller\Admin;

use App\Entity\Topic;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TopicCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Topic::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id');
        yield TextField::new('name');
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->disable(Action::DETAIL);
    }

}
