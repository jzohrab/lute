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


    // TODO:store_image_with_term?  Not sure if we should store the
    // image path in the data model.  Could add Term->imagePath(), or
    // TermImage->get() or similar.  Few terms will have images, so
    // storing them in the words table doesn't make much sense.
    //
    // Storing images explicitly with terms would allow for image
    // management, and maybe export to Anki etc in the future.
    //
    // If the images _are_ stored in the table, the term would have to
    // be saved first, obvs.  Could be managed by Dictionary->add().
    //
    // Images would *not* need to be added on bulk term save (e.g. on
    // "set all to known") because they wouldn't be there.
    //
    #[Route('/save', name: 'app_bing_save', methods: ['POST'])]
    public function bing_save(Request $request): JsonResponse
    {
        $src = $_POST['src'];
        $text = $_POST['text'];
        $langid = $_POST['langid'];
        // dump($src);

        $imgdir = __DIR__ . '/../../public/media/images/' . $langid;
        if (! file_exists($imgdir)) {
            mkdir($imgdir, 0777, true);
        }
        $img = $imgdir . '/' . $this->make_filename($text);
        file_put_contents($img, file_get_contents($src));

        return $this->json('ok');
    }

    // Returns the path of the image file if it exists, else empty string.
    #[Route('/get/{langid}/{text}', name: 'app_bing_get', methods: ['GET'])]
    public function bing_path(int $langid, string $text): JsonResponse
    {
        $reldir = __DIR__ . '/../../public';
        $imgdir = '/media/images/' . $langid;
        $realdir = $reldir . $imgdir;
        if (! file_exists($realdir)) {
            mkdir($realdir, 0777, true);
        }

        $f = $this->make_filename($text);
        $realfile = $realdir . '/' . $f;
        // dump('looking for ' . $realfile);
        if (! file_exists($realfile))
            return $this->json('');

        return $this->json($imgdir . '/' . $f);
    }
}
