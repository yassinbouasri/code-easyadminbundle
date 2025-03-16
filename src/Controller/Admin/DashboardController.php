<?php

namespace App\Controller\Admin;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Topic;
use App\Entity\User;
use App\Repository\QuestionRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SubMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractDashboardController
{
    public function __construct(private QuestionRepository $questionRepository)
    {
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route('/admin', name: 'admin')]
    public function index(ChartBuilderInterface $chartBuilder = null): Response
    {
        assert($chartBuilder !== null);

        $latestQuestions =  $this->questionRepository->findLatest();
        $topVoted = $this->questionRepository->findTopVoted();

        return $this->render('admin/index.html.twig', [
            'latestQuestions' => $latestQuestions,
            'topVoted' => $topVoted,
            'chart' => $this->createChart($chartBuilder),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Cauldron Overflow Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Content');
        yield MenuItem::subMenu('Questions', 'fas fa-question-circle')
            ->setSubItems([
                                MenuItem::linkToCrud('All', 'fa fa-list', Question::class)
                                            ->setPermission('ROLE_MODERATOR')
                                            ->setController(QuestionCrudController::class),
                                MenuItem::linkToCrud('Pending Approval', 'far fa-warning', Question::class)
                                              ->setPermission('ROLE_MODERATOR')
                                              ->setController(QuestionPendingApprovalCrudController::class),
                          ]);

        yield MenuItem::linkToCrud('Answers', 'fas fa-comments', Answer::class);
        yield MenuItem::linkToCrud('Topics', 'fas fa-folder', Topic::class);
        yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
        yield MenuItem::section();
        yield MenuItem::linkToUrl('HomePage', 'fa fa-home', $this->generateUrl('app_homepage'));
        yield MenuItem::linkToUrl('StackOverflow', 'fab fa-stack-overflow', 'https://stackoverflow.com')
            ->setLinkTarget('_blank');
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setAvatarUrl($user->getAvatarUrl())
            ->addMenuItems([
                MenuItem::linkToUrl('My Profile', 'fas fa-user', $this->generateUrl('app_profile_show'))
                           ]);
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->setDefaultSort(['id' => 'DESC'])
            ->overrideTemplate('crud/field/id', 'admin/id_with_icon.html.twig');
    }

    private function createChart(ChartBuilderInterface $chartBuilder): Chart
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
                            'labels' => ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                            'datasets' => [
                                [
                                    'label' => 'My First dataset',
                                    'backgroundColor' => 'rgb(255, 99, 132)',
                                    'borderColor' => 'rgb(255, 99, 132)',
                                    'data' => [0, 10, 5, 2, 20, 30, 45],
                                ],
                            ],
                        ]);
        $chart->setOptions([
                               'scales' => [
                                   'y' => [
                                       'suggestedMin' => 0,
                                       'suggestedMax' => 100,
                                   ],
                               ],
                           ]);
        return $chart;
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addWebpackEncoreEntry('admin');
    }




}
