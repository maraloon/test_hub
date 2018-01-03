<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Test;
use App\Form\SearchType;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TestController extends Controller
{
    /**
     * @Route("/tests/tag/{name}", name = "tests_by_tag")
     */
    public function testsByTag(Request $request, PaginatorInterface $paginator, Tag $tag)
    {
//        todo. It's emulating form submitting. Bad
//        cause of different logic of clicking tag (will find only tag)
//        and typing it's name (will find occurrences of text in tags and tests)
        $form = $this->createForm(
            SearchType::class,
            ['textValue' => $tag->getName()]
        );

        $em = $this->getDoctrine()->getManager();

        $tests = $em->getRepository(Test::class)->findByTag($tag);
        $tests = $this->paginateTests($request, $paginator, $tests);

        return $this->render('tests/list.html.twig', [
            'tests' => $tests,
            'searchForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/tests/search", name = "search")
     */
    public function searchList(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(SearchType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $searchString = $form->getData()['text'];
        } else {
            $searchString = $request->query->get('text');
        }

        if ($searchString) {
            $em = $this->getDoctrine()->getManager();

            $tests = $em->getRepository(Test::class)->findByNameOrTagInclusions($searchString);
            $tests = $this->paginateTests($request, $paginator, $tests);

            return $this->render('tests/list.html.twig', [
                'tests' => $tests,
                'searchForm' => $form->createView(),
            ]);
        } else {
            //todo customize error pages
            throw $this->createNotFoundException('There is nothing to search');
        }
    }

    /**
     * @Route("/tests", name = "tests")
     */
    public function list(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(SearchType::class);
        $em = $this->getDoctrine()->getManager();
        $tests = $em->getRepository(Test::class)->findAll();

        $tests = $this->paginateTests($request, $paginator, $tests);

        return $this->render('tests/list.html.twig', [
            'tests' => $tests,
            'searchForm' => $form->createView(),
        ]);
    }

    private function paginateTests(Request $request, PaginatorInterface $paginator, array $tests): PaginationInterface
    {
        return $paginator->paginate(
            $tests,
            $request->query->getInt('page', 1),
            10
        );
    }
}