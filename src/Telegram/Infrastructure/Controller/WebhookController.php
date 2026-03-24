<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Controller;

use App\Telegram\Infrastructure\WebhookProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class WebhookController
{
    #[Route('/webhook', methods: ['POST'])]
    public function __invoke(Request $request, WebhookProcessor $processor): Response
    {
        try {
            $processor->process($request->getContent());
        } catch (\Throwable) {
            // Always return 200 to prevent Telegram from retrying and banning the webhook
        }

        return new Response('', Response::HTTP_OK);
    }
}
