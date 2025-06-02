<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

final class SimpleController
{
    public function index(): Response
    {
        return new Response('Hello, World!');
    }
}
