<?php

namespace App\Controller;

use App\Domain\ReadingFacade;
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
        [ $prev, $next ] = $facade->get_prev_next($text);
        return $this->render('read/index.html.twig', [
            'text' => $text,
            'prevtext' => $prev,
            'nexttext' => $next
        ]);
    }

    #[Route('/text/{TxID}', name: 'app_read_text', methods: ['GET'])]
    public function text(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $facade->set_current_text($text);
        $sentences = $facade->getSentences($text);
        return $this->render('read/text.html.twig', [
            'dictionary_url' => $text->getLanguage()->getLgGoogleTranslateURI(),
            'sentences' => $sentences
        ]);
    }

    #[Route('/termform/{wid}/{textid}/{ord}/{text}', name: 'app_term_load', methods: ['GET', 'POST'])]
    public function term_form(
        int $wid,
        int $textid,
        int $ord,
        string $text,
        Request $request,
        ReadingFacade $facade
    ): Response
    {
        // The $text is '-' if missing,
        // b/c otherwise the route didn't work.
        if ($text == '-')
            $text = '';

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

        $termdto = $facade->loadDTO($wid, $textid, $ord, $text);
        $form = $this->createForm(TermDTOType::class, $termdto, [ 'hide_sentences' => true ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            [ $updateitems, $update_js ] = $facade->saveDTO($termdto, $textid);

            // The updates are encoded here, and decoded in the
            // twig javascript.  Thanks to
            // https://stackoverflow.com/questions/38072085/
            //   how-to-render-json-into-a-twig-in-symfony2
            return $this->render('read/updated.html.twig', [
                'termdto' => $termdto,
                'textitems' => $updateitems,
                'updates' => json_encode($update_js)
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

    #[Route('/{TxID}/allknown', name: 'app_read_allknown', methods: ['POST'])]
    public function allknown(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $facade->mark_unknowns_as_known($text);
        return $this->redirectToRoute(
            'app_read',
            [ 'TxID' => $text->getID() ],
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
