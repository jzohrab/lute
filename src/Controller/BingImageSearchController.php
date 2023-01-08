<?php

namespace App\Controller;

use App\Entity\Language;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bing')]
class BingImageSearchController extends AbstractController
{

    #[Route('/search/{langid}/{text}/{searchstring?-}', name: 'app_bing_search', methods: ['GET'])]
    public function bing_search(int $langid, string $text, string $searchstring): Response
    {
        // dump("searching for " . $text . " in " . $language->getLgName());
        $search = rawurlencode($text);
        $searchparams = str_replace("###", $search, $searchstring);
        $url = "https://www.bing.com/images/search?" . $searchparams;
        $content = file_get_contents($url);

        // Samples
        // <img class="mimg vimgld" ... data-src="https:// ...">
        // or
        // <img class="mimg rms_img" ... src="https://tse4.mm.bing ..." >
        
        $pattern = "/(<img .*?>)/i";
        preg_match_all($pattern, $content, $matches, PREG_PATTERN_ORDER);
        
        $images = array_values($matches[1]);

        $is_search_img = function($img) {
            if (strstr($img, 'src="/'))
                return false;
            return strstr($img, 'rms_img') ||
                strstr($img, 'vimgld');
        };
        $images = array_filter($images, $is_search_img);

        $fix_data_src = function($img) {
            return str_replace('data-src=', 'src=', $img);
        };
        $images = array_map($fix_data_src, $images);

        // Reduce image load count so we don't kill subpage loading.
        $images = array_slice($images, 0, 25);

        $build_struct = function($image) {
            $src = 'missing';
            $ret = preg_match('/src="(.*?)"/', $image, $matches, PREG_OFFSET_CAPTURE);
            if ($ret == 1)
                $src = $matches[1][0];
            return [
                'html' => $image,
                'src' => $src
            ];
        };
        $data = array_map($build_struct, $images);

        return $this->render('imagesearch/index.html.twig', [
            'langid' => $langid,
            'text' => $text,
            'images' => $data
        ]);
    }

    /**
     * Make a filename for the text.
     * php's file_exists() doesn't work if the filename contains spaces.
     */
    private function make_filename(string $text): string
    {
        $ret = preg_replace('/\s+/u', '_', $text) . '.jpeg';
        return $ret;
    }


    // Save the image to disk, and return the new filename so it can
    // be saved as a new TermImage.
    #[Route('/save', name: 'app_bing_save', methods: ['POST'])]
    public function bing_save(Request $request): JsonResponse
    {
        $src = $_POST['src'];
        $text = $_POST['text'];
        $langid = $_POST['langid'];

        $publicdir = '/userimages/' . $langid . '/';
        $realdir = __DIR__ . '/../../public' . $publicdir;
        if (! file_exists($realdir)) {
            mkdir($realdir, 0777, true);
        }
        $f = $this->make_filename($text);
        file_put_contents($realdir . $f, file_get_contents($src));

        return $this->json([ 'filename' => $publicdir . $f ]);
    }

}
