<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Controller;

use App\Telegram\Infrastructure\WebhookProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookController
{
    #[Route('/webhook', methods: ['POST'])]
    public function __invoke(Request $request, WebhookProcessor $processor): Response
    {
        $processor->process($request->getContent());

        return new Response('', Response::HTTP_OK);
    }
}
