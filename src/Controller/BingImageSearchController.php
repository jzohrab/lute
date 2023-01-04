<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bing')]
class BingImageSearchController extends AbstractController
{

    #[Route('/term/{v?-}', name: 'app_bingsearch', methods: ['GET'])]
    public function getit(string $v): Response
    {

        $searchv = rawurlencode($v);
        $url = "https://www.bing.com/images/search?q={$searchv}&qs=n&form=QBIR&sp=-1&pq={$searchv}&sc=3-21&cvid=2040690D7A154D8B998A5437C689BE2A&ghsh=0&ghacc=0&first=1&tsc=ImageHoverTitle";
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
