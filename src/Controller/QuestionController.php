<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use App\Service\MarkdownHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    private $logger;
    private $isDebug;

    public function __construct(LoggerInterface $logger, bool $isDebug)
    {
        $this->logger = $logger;
        $this->isDebug = $isDebug;
    }

    #[Route('/', name: 'app_homepage')]
    public function homepage(QuestionRepository $repository)
    {
        $questions = $repository->findAllApprovedOrderedByNewest();

        return $this->render('question/homepage.html.twig', [
            'questions' => $questions,
        ]);
    }

    #[Route('/questions/new')]
    public function new()
    {
        return new Response('Sounds like a GREAT feature for V2!');
    }

    #[Route('/questions/{slug}', name: 'app_question_show')]
    public function show(Question $question)
    {
        if (!$question->getIsApproved()) {
            throw $this->createNotFoundException(sprintf('Question %s has not been approved yet', $question->getId()));
        }

        if ($this->isDebug) {
            $this->logger->info('We are in debug mode!');
        }

        return $this->render('question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/questions/{slug}/vote', name: 'app_question_vote', methods: 'POST')]
    public function questionVote(Question $question, Request $request, EntityManagerInterface $entityManager)
    {
        $direction = $request->request->get('direction');

        if ($direction === 'up') {
            $question->upVote();
        } elseif ($direction === 'down') {
            $question->downVote();
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_question_show', [
            'slug' => $question->getSlug()
        ]);
    }

}
