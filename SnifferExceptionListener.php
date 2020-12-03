<?php


namespace App\Sniffer;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class SnifferExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof SnifferException) {
            $event->setResponse(
                new JsonResponse(
                    $this->isMessageJson($exception->getMessage())
                        ? json_decode($exception->getMessage())
                        : $exception->getMessage(),
                    Response::HTTP_BAD_REQUEST
                )
            );
        }
    }

    private function isMessageJson($message)
    {
        json_decode($message);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}