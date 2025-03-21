<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use function Symfony\Component\Translation\t;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function new(AdminContext $context)
    {
        if (!$context){
            throw new \RuntimeException('Admin context is not defined');
        }
        return parent::new($context); // TODO: Change the autogenerated stub
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();
        yield AvatarField::new('avatar')
            ->onlyOnIndex()
            ->formatValue(static function ($value,?User $user) {
                return $user?->getAvatarUrl();
            });
        yield ImageField::new('avatar')
            ->onlyOnForms()
            ->setBasePath('uploads/avatars')
            ->setUploadDir('public/uploads/avatars')
            ->setUploadedFileNamePattern('[slug]-[timestamp]-[filename].[extension]');

        yield EmailField::new('email');
        yield TextField::new('password')
            ->onlyOnForms()
            ->setFormType(PasswordType::class);
        yield TextField::new('fullName')
            ->hideOnForm();
        yield TextField::new('firstName')
            ->onlyOnForms();
        yield TextField::new('lastName')
            ->onlyOnForms();
        yield BooleanField::new('enabled')
            ->renderAsSwitch(false);
        yield DateTimeField::new('createdAt')
            ->hideOnForm();

        $roles = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_MODERATOR', 'ROLE_USER'];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges()
            ->setPermission('ROLE_SUPER_ADMIN');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityPermission('ADMIN_USER_EDIT');
    }

    public function createIndexQueryBuilder(
        SearchDto        $searchDto,
        EntityDto        $entityDto,
        FieldCollection  $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $query = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if($this->isGranted('ROLE_SUPER_ADMIN')){
            return $query;
        }

        return $query->andWhere('entity.id = :id')
            ->setParameter('id', $this->getUser()->getId());
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(BooleanFilter::new('enabled')->setFormTypeOptions([
                                                            'expanded' => false
                                                                    ]));
    }


}
