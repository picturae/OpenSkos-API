<?php

declare(strict_types=1);

namespace App\OpenSkos\Exception\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

final class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        /* $argument = $event->getException(); */
        /* var_dump($response); */

        /* echo "<pre>"; */
        /* /1* foreach (func_get_args() as $argument) { *1/ */
        /*     $type = gettype($argument); */
        /*     echo $type; */
        /*     if ('object' === $type) { */
        /*         $class = get_class($argument); */
        /*         echo " (${class})"; */
        /*     } */
        /*     echo "<br />"; */
        /*     echo json_encode($argument); */
        /*     echo "<br /><br />"; */
        /* /1* } *1/ */
        /* die(); */

        /* ob_start(); */
        /* var_dump(func_get_args()); */
        /* $dump = ob_get_clean(); */
        /* die(substr($dump,0,1024)); */
    }
}
