<?php

namespace App\Controller\Admin;

use App\EasyAdmin\VotesField;
use App\Entity\Question;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

//#[IsGranted("ROLE_SUPER_ADMIN")] // Restricting Access to an Entire Crud Section
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
            ->formatValue(static function ($value,?Question $question) {
                if (!$user = $question?->getAskedBy()) {
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
            ->setFormTypeOption('by_reference', false);
        yield DateField::new('createdAt')
            ->onlyOnIndex();

        yield AssociationField::new('updatedBy')
            ->onlyOnDetail();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort([
                'askedBy.enabled' => 'DESC',
                'createdAt' => 'DESC',
                             ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewAction = function () {
            return Action::NEW('view')
                  ->linkToUrl(function(Question $question){
                      return $this->generateUrl('app_question_show', [
                          'slug' => $question->getSlug()
                      ]);
                  })
                  ->setIcon('fas fa-eye')
                  ->setLabel('View on site');
        };

        return parent::configureActions($actions)
//            ->update(Crud::PAGE_INDEX,Action::DELETE, function (Action $action) {
//                $action->displayIf(static function (Question $question) {
//                    return !$question->getAskedBy();
//                });
//                return $action;
//            })
            ->setPermission(Action::INDEX, 'ROLE_MODERATOR')
            ->setPermission(Action::DETAIL, 'ROLE_MODERATOR')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->add(Action::DETAIL,$viewAction()->addCssClass('btn btn-sm btn-success'))
            ->add(Action::INDEX, $viewAction());
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add('topic')
            ->add('askedBy')
            ->add('createdAt')
            ->add('votes')
            ->add('name');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        //Same as create a Event Subscriber
        if (!$entityInstance instanceof Question){
            return;
        }
        $user = $this->getUser();

        if (!$user instanceof User){
            throw new \LogicException('User should be an instance of User');
        }

        $entityInstance->setUpdatedBy($user);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
//        if ($entityInstance->getIsApproved()) {
//            throw new \Exception('Deleting approved question is forbidden');
//        }

        parent::deleteEntity($entityManager, $entityInstance);
    }


}
