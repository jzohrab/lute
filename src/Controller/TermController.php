<?php

namespace App\Controller;

use App\Entity\Term;
use App\Entity\Language;
use App\DTO\TermDTO;
use App\Form\TermDTOType;
use App\Repository\TermRepository;
use App\Repository\TermTagRepository;
use App\Repository\LanguageRepository;
use App\Domain\TermService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/term')]
class TermController extends AbstractController
{
    #[Route('/index/{search?}', name: 'app_term_index', methods: ['GET'])]
    public function index(?string $search): Response
    {
        // Can pass an initial search string.  If nothing is passed, $search = null.
        return $this->render('term/index.html.twig', [
            'initial_search' => $search
        ]);
    }

    #[Route('/datatables', name: 'app_term_datatables', methods: ['POST'])]
    public function datatables_source(Request $request, TermRepository $repo): JsonResponse
    {
        $parameters = $request->request->all();
        $data = $repo->getDataTablesList($parameters);
        $data["draw"] = $parameters['draw'];
        return $this->json($data);
    }

    #[Route('/bulk_set_parent', name: 'app_term_bulk_set_parent', methods: ['POST'])]
    public function bulk_set_parent(
        Request $request,
        TermRepository $term_repo,
        LanguageRepository $lang_repo,
        TermService $term_svc
    ): JsonResponse
    {
        $parameters = $request->request->all();
        $wordids = $parameters['wordids'];
        $parenttext = trim($parameters['parenttext']);
        $langid = intval($parameters['langid']);

        // dump($wordids);
        // dump($parenttext);
        // dump($langid);

        $lang = $lang_repo->find($langid);
        $parent = null;
        if ($parenttext != '') {
            $parent = $term_svc->find($parenttext, $lang);
            if ($parent == null) {
                $parent = new Term($lang, $parenttext);
            }
        }
        $pid = null;
        if ($parent != null)
            $pid = $parent->getID();

        $terms = array_map(fn($n) => $term_repo->find(intval($n)), $wordids);
        $update = array_filter(
            $terms,
            fn($t) => ($t->getLanguage()->getLgID() == $langid) && ($t->getID() != $pid)
        );
        foreach ($update as $t) {
            $t->setParent($parent);
            $term_repo->save($t, true);
        }
        return $this->json('ok');
    }


    #[Route('/search/{text}/{langid}', name: 'app_term_search', methods: ['GET'])]
    public function search_by_text_in_language(
        $text,
        $langid,
        LanguageRepository $lang_repo,
        TermService $term_service
    ): JsonResponse
    {
        $lang = $lang_repo->find($langid);
        $terms = $term_service->findMatches($text, $lang);
        $result = [];
        foreach ($terms as $t) {
            $trans = $t->getTranslation();
            $result[] = [
                'id' => $t->getID(),
                'text' => $t->getTextLC(),
                'translation' => $t->getTranslation()
            ];
        }
        return $this->json($result);
    }


    private function processTermForm(
        \Symfony\Component\Form\Form $form,
        Request $request,
        TermDTO $termdto,
        TermService $term_svc,
        TermTagRepository $termtag_repo
    ): ?Response
    {
        $form->handleRequest($request);
        $submitted_valid = $form->isSubmitted() && $form->isValid();
        if (! $submitted_valid)
            return null;

        $term = TermDTO::buildTerm($termdto, $term_svc, $termtag_repo);
        try {
            $term_svc->add($term);
            return $this->redirectToRoute('app_term_index', [], Response::HTTP_SEE_OTHER);
        }
        catch (\Exception $e) {
            $errcode = intval($e->getCode());
            $INTEGRITY_CONSTRAINT_VIOLATION = 1062;
            if ($errcode != $INTEGRITY_CONSTRAINT_VIOLATION) {
                // Some different error, throw b/c I'm not sure what's
                // happening.
                throw $e;
            }

            $msg = $term->getText() . " already exists.";
            $this->addFlash('notice', $msg);
            $existing = $term_svc->find($term->getTextLC(), $term->getLanguage());
            return $this->redirectToRoute(
                'app_term_edit',
                [ 'id' => $existing->getID() ],
                Response::HTTP_SEE_OTHER
            );
        }
    }


    #[Route('/new', name: 'app_term_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TermService $term_svc, TermTagRepository $termtag_repo): Response
    {
        $dto = new TermDTO();
        $form = $this->createForm(TermDTOType::class, $dto);
        $resp = $this->processTermForm($form, $request, $dto, $term_svc, $termtag_repo);
        if ($resp != null)
            return $resp;

        return $this->renderForm('term/formframes.html.twig', [
            'termdto' => $dto,
            'form' => $form,
            'showlanguageselector' => true,
            'disabletermediting' => false
        ]);
    }


    #[Route('/sentences/{LgID}/{text}', name: 'app_term_sentences', methods: ['GET'])]
    public function show_sentences(Language $lang, string $text, TermService $term_svc): Response
    {
        $term = $term_svc->find($text, $lang);
        if ($term == null)
            $term = new Term($lang, $text);
        $refs = $term_svc->findReferences($term);

        // Ref https://stackoverflow.com/questions/2517947/
        //  ucfirst-function-for-multibyte-character-encodings
        $mb_ucfirst = function ($string, $encoding = null)
        {
            if ($encoding === null)
                $encoding = mb_internal_encoding();
            $firstChar = mb_substr($string, 0, 1, $encoding);
            $then = mb_substr($string, 1, null, $encoding);
            return mb_strtoupper($firstChar, $encoding) . $then;
        };

        $ptext = 'Parent';
        $p = $term->getParent();
        if ($p != null)
            $ptext = $mb_ucfirst($p->getText());

        $references = [
            $mb_ucfirst($term->getText()) => $refs['term'],
            $ptext => $refs['parent'],
            'Term children' => $refs['children'],
            'Sibling terms (with same parent ' . $ptext . ')' => $refs['siblings']
        ];

        return $this->render(
            'term/sentences.html.twig',
            [ 'references' => $references ]
        );
    }


    #[Route('/{id}', name: 'app_term_show', methods: ['GET'])]
    public function show(Term $term): Response
    {
        return $this->render('term/show.html.twig', [
            'term' => $term,
            'termdto' => $term->createTermDTO()
        ]);
    }

    #[Route('/{id}/edit', name: 'app_term_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Term $term, TermService $term_svc, TermTagRepository $termtag_repo): Response
    {
        $dto = $term->createTermDTO();
        $form = $this->createForm(TermDTOType::class, $dto);
        $resp = $this->processTermForm($form, $request, $dto, $term_svc, $termtag_repo);
        if ($resp != null)
            return $resp;

        return $this->renderForm('term/formframes.html.twig', [
            'termdto' => $dto,
            'form' => $form,
            'showlanguageselector' => false,
            'disabletermediting' => true
        ]);
    }

    #[Route('/{id}', name: 'app_term_delete', methods: ['POST'])]
    public function delete(Request $request, Term $term, TermService $term_svc): Response
    {
        $reqtok = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$term->getId(), $reqtok)) {
            $term_svc->remove($term);
        }

        return $this->redirectToRoute('app_term_index', [], Response::HTTP_SEE_OTHER);
    }

}
