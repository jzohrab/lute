<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Psr\Log\LoggerInterface;

// Thanks to https://stackoverflow.com/questions/48166213/how-to-clear-cache-in-controller-in-symfony3-4
class CacheClearController extends AbstractController
{

    #[Route('/cache/clear', name: 'app_cache_clear', methods: ['GET'])]
    public function clear_cache(KernelInterface $kernel, LoggerInterface $logger): Response
    {
        $this->do_commands($kernel, [ 'cache:clear' ], $logger);
        // Apparently there is no need to call 'cache:warmup', it
        // appears to be included in the output.
        
        $this->addFlash('notice', 'Cache cleared.');
        return $this->redirectToRoute('app_index');
    }

    private function do_commands($kernel, $commands, $logger)
    {
        $env = $kernel->getEnvironment();
        
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();

        foreach ($commands as $command) {
            $output->write($command . " ============================", true);
            $input = new ArrayInput(array(
                'command' => $command,
                '--env' => $env
            ));
            $application->run($input, $output);
            $output->write("END " . $command . " ============================", true);
        }

        $content = $output->fetch();

        $logger->info($content);
    }

}
