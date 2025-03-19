<?php

namespace App\Controller\Admin;

use App\EasyAdmin\VotesField;
use App\Entity\Question;
use App\Entity\User;
use App\Service\CsvExporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Exception;
use LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse;

//#[IsGranted("ROLE_SUPER_ADMIN")] // Restricting Access to an Entire Crud Section
class QuestionCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Question::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)->setDefaultSort(
            [
                'askedBy.enabled' => 'DESC',
                'createdAt'       => 'DESC',
            ]
        );
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewAction = function ()
            {
                return Action::NEW('view')->linkToUrl(
                    function (Question $question)
                        {
                            return $this->generateUrl(
                                'app_question_show',
                                [
                                    'slug' => $question->getSlug(),
                                ]
                            );
                        }
                )->setIcon('fas fa-eye')->setLabel('View on site');
            };

        $approveAction = Action::new('approve')->linkToCrudAction('approve')->addCssClass('btn btn-success')->setIcon(
            'fas fa-check-circle'
        )->setTemplatePath('admin/approve_action.html.twig')->displayIf(
            static function (Question $question): bool
                {
                    return !$question->getIsApproved();
                }
        )->displayAsButton();

        $exportAction = Action::new('export')->linkToCrudAction('export')->addCssClass('btn btn-secondary')->setIcon(
            'fas fa-download'
        )->createAsGlobalAction();

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
            ->add(Crud::PAGE_DETAIL, $viewAction()->addCssClass('btn btn-sm btn-success'))
            ->add(Crud::PAGE_INDEX, $viewAction())
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_INDEX, $exportAction)
            ->reorder(Crud::PAGE_DETAIL, [
                'approve',
                'view',
                Action::EDIT,
                Action::INDEX,
                Action::DELETE,
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)->add('topic')->add('askedBy')->add('createdAt')->add('votes')->add(
            'name'
        );
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        //Same as create a Event Subscriber
        if (!$entityInstance instanceof Question) {
            return;
        }
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new LogicException('User should be an instance of User');
        }

        $entityInstance->setUpdatedBy($user);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getIsApproved()) {
            throw new Exception('Deleting approved question is forbidden');
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function approve(
        AdminContext $adminContext,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator
    ): RedirectResponse {
        $question = $adminContext->getEntity()->getInstance();

        if (!$question instanceof Question) {
            throw new LogicException('Question should be an instance of Question');
        }
        $question->setIsApproved(true);
        $entityManager->flush();

        $targetUrl = $adminUrlGenerator->setController(self::class)->setAction(Crud::PAGE_INDEX)->setEntityId(
            $question->getId()
        )->generateUrl();

        return $this->redirect($targetUrl);
    }

    public function export(AdminContext $context, CsvExporter $csvExporter)
    {
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create(
            $context->getCrud()->getFiltersConfig(),
            $fields,
            $context->getEntity()
        );
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);

        return $csvExporter->createResponseFromQueryBuilder($queryBuilder, $fields, 'questions.csv');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield Field::new('slug')->hideOnIndex()->setFormTypeOption('disabled', $pageName !== Crud::PAGE_NEW);
        yield TextField::new('name');
        yield AssociationField::new('topic')->autocomplete();
        yield TextAreaField::new('question')->hideOnIndex()->setFormTypeOptions(
            [
                'row_attr' => [
                    'data-controller' => 'snarkdown',
                ],
                'attr'     => [
                    'data-snarkdown-target' => 'input',
                    'data-action'           => 'snarkdown#render',
                ],
            ]
        )->setHelp('Preview:');
        yield VotesField::new('votes', 'Total votes')->setTextAlign('center');
        yield AssociationField::new('askedBy')->autocomplete()->formatValue(
            static function ($value, ?Question $question)
                {
                    if (!$user = $question?->getAskedBy()) {
                        return null;
                    }

                    return sprintf('%s&nbsp;(%s)', $user->getEmail(), $user->getQuestions()->count());
                }
        )->setQueryBuilder(
            function (QueryBuilder $queryBuilder)
                {
                    $queryBuilder->andWhere('entity.enabled = :enabled');
                    $queryBuilder->setParameter('enabled', true);
                }
        );
        yield AssociationField::new('answers')->autocomplete()->setFormTypeOption('by_reference', false);
        yield DateField::new('createdAt')->onlyOnIndex();

        yield AssociationField::new('updatedBy')->onlyOnDetail();
    }

}
