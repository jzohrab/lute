<?php

namespace App\Controller;

use App\Domain\ReadingFacade;
use App\Domain\BookStats;
use App\Repository\TextRepository;
use App\Repository\TermRepository;
use App\Repository\ReadingRepository;
use App\DTO\TermDTO;
use App\Entity\Text;
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
        return $this->render('read/empty.html.twig');
    }

    // Note this route has to appear about "read" because otherwise
    // 'shortcuts' is treated as a TxID.
    #[Route('/shortcuts', name: 'app_read_shortcuts', methods: ['GET'])]
    public function shortcuts(Request $request): Response
    {
        return $this->render('read/shortcuts.html.twig');
    }


    #[Route('/{TxID}', name: 'app_read', methods: ['GET'])]
    public function read(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $facade->set_current_book_text($text);

        $book = $text->getBook();
        BookStats::markStale($book);
        [ $prev, $next ] = $facade->get_prev_next($text);
        [ $prev10, $next10 ] = $facade->get_prev_next_by_10($text);
        return $this->render('read/index.html.twig', [
            'text' => $text,
            'book' => $book,
            'pagenum' => $text->getOrder(),
            'pagecount' => $book->getPageCount(),
            'prevtext' => $prev,
            'prevtext10' => $prev10,
            'nexttext' => $next,
            'nexttext10' => $next10,
        ]);
    }

    #[Route('/text/{TxID}', name: 'app_read_text', methods: ['GET'])]
    public function text(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $facade->set_current_text($text);
        $sentences = $facade->getSentences($text);
        return $this->render('read/text.html.twig', [
            'textid' => $text->getId(),
            'dictionary_url' => $text->getLanguage()->getLgGoogleTranslateURI(),
            'sentences' => $sentences
        ]);
    }

    #[Route('/sentences/{TxID}', name: 'app_read_sentences', methods: ['GET'])]
    public function sentences(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $sentences = $facade->getSentences($text);
        return $this->render('read/sentences.html.twig', [
            'sentences' => $sentences
        ]);
    }

    #[Route('/termform/{lid}/{text}', name: 'app_term_load', methods: ['GET', 'POST'])]
    public function term_form(
        int $lid,
        string $text,
        Request $request,
        ReadingFacade $facade
    ): Response
    {
        // When a term is created in the form, the spaces passed by
        // the form are "nbsp;" = non-breaking spaces, which are
        // actually different from regular spaces, as seen by the
        // database.  Without the below fix to the space characters, a
        // Term with text "hello there" will not match a database
        // sentence "she said hello there".
        $zws = mb_chr(0x200B); // zero-width space.
        $parts = explode($zws, $text);
        $cleanspaces = function($s) { return preg_replace('/\s/u', ' ', $s); };
        $cleanedparts = array_map($cleanspaces, $parts);
        $text = implode($zws, $cleanedparts);

        $termdto = $facade->loadDTO($lid, $text);
        $form = $this->createForm(TermDTOType::class, $termdto, [ 'hide_sentences' => true ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $facade->saveDTO($termdto);
            return $this->render('read/updated.html.twig', [
                'termdto' => $termdto
            ]);
        }

        return $this->renderForm('read/frameform.html.twig', [
            'termdto' => $termdto,
            'form' => $form,
            'extra' => $request->query,
            'showlanguageselector' => false,
            'disabletermediting' => true,
            'parent_link_to_frame' => true,
        ]);
    }

    #[Route('/{TxID}/allknown/{nexttextid?}', name: 'app_read_allknown', methods: ['POST'])]
    public function allknown(Request $request, ?int $nexttextid, Text $text, ReadingFacade $facade): Response
    {
        $facade->mark_unknowns_as_known($text);
        $showid = $nexttextid ?? $text->getID();
        return $this->redirectToRoute(
            'app_read',
            [ 'TxID' => $showid ],
            Response::HTTP_SEE_OTHER
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
        $textid = intval($prms['textid']);
        $newstatus = intval($prms['new_status']);
        $text = $textRepository->find($textid);

        $facade->update_status($text, $words, $newstatus);

        return $this->json('ok');
    }

}
