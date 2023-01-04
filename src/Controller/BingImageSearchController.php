<?php

namespace App\Controller;

use App\Entity\Language;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bing')]
class BingImageSearchController extends AbstractController
{

    #[Route('/term/{text}/{searchstring?-}', name: 'app_bingsearch', methods: ['GET'])]
    public function bing_search(string $text, string $searchstring): Response
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

        // Don't kill the sub-page.
        $images = array_slice($images, 0, 25);

        $list = implode('; ', $images);
        $r = new Response($list);
        return $r;
    }
}
