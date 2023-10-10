<?php

namespace App\Controller;

use App\Domain\ReadingFacade;
use App\Domain\BookStats;
use App\Repository\TextRepository;
use App\Repository\TermRepository;
use App\DTO\TermDTO;
use App\Entity\Book;
use App\Entity\Text;
use App\Entity\Language;
use App\Entity\Sentence;
use App\Form\TermDTOType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/read')]
class ReadingController extends AbstractController
{

    #[Route('/empty', name: 'app_read_empty', methods: ['GET'])]
    public function empty(Request $request): Response
    {
        // A dummy hack to clear out the dictionary pane.  Annoying!
        // This is just HTML.  Could likely be moved out of the
        // controller entirely, but keeping it here for uniformity.
        return $this->render('read/empty.html.twig');
    }

    #[Route('/flashcopied', name: 'app_read_flashcopied', methods: ['GET'])]
    public function flashcopied(Request $request): Response
    {
        // This is just HTML.  Could likely be moved out of the
        // controller entirely, but keeping it here for uniformity.
        return $this->render('read/flashcopied.html.twig');
    }

    // Note this route has to appear about "read" because otherwise
    // 'shortcuts' is treated as a TxID.
    #[Route('/shortcuts', name: 'app_read_shortcuts', methods: ['GET'])]
    public function shortcuts(Request $request): Response
    {
        return $this->render('read/shortcuts.html.twig');
    }


    #[Route('/{BkID}/page/{pagenum}', name: 'app_read', methods: ['GET'])]
    public function read(
        Book $book,
        int $pagenum,
        TextRepository $textRepository,
        ReadingFacade $facade
    ): Response
    {
        $pc = $book->getPageCount();
        $pageInRange = function($n) use ($pc) {
            if ($n < 1)
                $n = 1;
            if ($n > $pc)
                $n = $pc;
            return $n;
        };

        $pagenum = $pageInRange($pagenum);
        $prev = $pageInRange($pagenum - 1);
        $next = $pageInRange($pagenum + 1);
        $prev10 = $pageInRange($pagenum - 10);
        $next10 = $pageInRange($pagenum + 10);

        $text = $textRepository->getTextAtPageNumber($book, $pagenum);
        $facade->set_current_book_text($text);
        BookStats::markStale($book);

        return $this->render('read/index.html.twig', [
            'text' => $text,
            'htmltitle' => $text->getTitle(),
            'book' => $book,
            'pagenum' => $pagenum,
            'pagecount' => $book->getPageCount(),
            'prevpage' => $prev,
            'prev10page' => $prev10,
            'nextpage' => $next,
            'next10page' => $next10,
        ]);
    }

    #[Route('/text/{TxID}', name: 'app_read_text', methods: ['GET'])]
    public function text(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $book = $text->getBook();
        $lang = $book->getLanguage();
        $isRTL = $lang->isLgRightToLeft() ?? false;

        $paragraphs = $facade->getParagraphs($text);
        return $this->render('read/text.html.twig', [
            'textid' => $text->getId(),
            'isRTL' => $isRTL,
            'dictionary_url' => $text->getLanguage()->getLgGoogleTranslateURI(),
            'paragraphs' => $paragraphs
        ]);
    }

    #[Route('/sentences/{TxID}', name: 'app_read_sentences', methods: ['GET'])]
    public function sentences(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $paragraphs = $facade->getParagraphs($text);
        return $this->render('read/sentences.html.twig', [
            'paragraphs' => $paragraphs
        ]);
    }

    #[Route('/termform/{LgID}/{text}', name: 'app_term_load', methods: ['GET', 'POST'])]
    public function term_form(
        Language $lang,
        string $text,
        Request $request,
        ReadingFacade $facade
    ): Response
    {
        // TODO:duplicate_code? - this code is practically a dup of
        // TermController->processTermForm().

        // When a term is created in the form, the spaces passed by
        // the form are "nbsp;" = non-breaking spaces, which are
        // actually different from regular spaces, as seen by the
        // database.  Without the below fix to the space characters, a
        // Term with text "hello there" will not match a database
        // sentence "she said hello there".
        $usetext = preg_replace('/\s/u', ' ', $text);

        // Undo the "annoying hack" sent by lute.js to handle '.'
        // character.
        $usetext = preg_replace('/__LUTE_PERIOD__/u', '.', $text);

        $termdto = $facade->loadDTO($lang, $usetext);
        $form = $this->createForm(TermDTOType::class, $termdto, [ 'hide_sentences' => true ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Front-end should prevent text changing.
            if ($termdto->textHasChanged()) {
                $msg = "Can only change term case.";
                $this->addFlash('error', $msg);
                $termdto->Text = $usetext;
                $termdto->OriginalText = $usetext;
                $form = $this->createForm(TermDTOType::class, $termdto, [ 'hide_sentences' => true ]);
            }
            else {
                $facade->saveDTO($termdto);
                return $this->render('read/updated.html.twig', [
                    'termdto' => $termdto
                ]);
            }
        }

        return $this->renderForm('read/frameform.html.twig', [
            'termdto' => $termdto,
            'form' => $form,
            'extra' => $request->query,
            'showlanguageselector' => false,
            'parent_link_to_frame' => true,
        ]);
    }


    private function pageRead(
        Book $book,
        int $pagenum,
        ?int $nextpage,
        bool $mark_unknowns_as_known,
        TextRepository $textRepository,
        ReadingFacade $facade
    )
    {
        $text = $textRepository->getTextAtPageNumber($book, $pagenum);
        if ($mark_unknowns_as_known) {
            $facade->mark_unknowns_as_known($text);
        }
        $facade->mark_read($text);
        $nextpage = $nextpage ?? $pagenum;
        return $this->redirectToRoute(
            'app_read',
            [ 'BkID' => $book->getID(), 'pagenum' => $nextpage ],
            Response::HTTP_SEE_OTHER
        );
    }
        
    #[Route('/{BkID}/page/{pagenum}/allknown/{nextpage?}', name: 'app_read_allknown', methods: ['POST'])]
    public function allknown(
        Book $book,
        int $pagenum,
        TextRepository $textRepository,
        ?int $nextpage,
        ReadingFacade $facade
    ): Response
    {
        return $this->pageRead(
            $book, $pagenum, $nextpage, true, $textRepository, $facade
        );
    }

    #[Route('/{BkID}/page/{pagenum}/markread/{nextpage?}', name: 'app_mark_read', methods: ['POST'])]
    public function mark_read(
        Book $book,
        int $pagenum,
        TextRepository $textRepository,
        ?int $nextpage,
        ReadingFacade $facade
    ): Response
    {
        return $this->pageRead(
            $book, $pagenum, $nextpage, false, $textRepository, $facade
        );
    }


    #[Route('/update_status', name: 'app_read_update_status', methods: ['POST'])]
    public function update_status(
        Request $request,
        ReadingFacade $facade,
        TextRepository $textRepository
    ): JsonResponse
    {
        $prms = $request->request->all();
        $words = $prms['terms'];
        // dump('in /update_status, updating words = ' . implode(', ', $words));
        $textid = intval($prms['textid']);
        $newstatus = intval($prms['new_status']);
        $text = $textRepository->find($textid);

        $facade->update_status($text, $words, $newstatus);

        return $this->json('ok');
    }

}
