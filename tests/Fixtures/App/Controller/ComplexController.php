<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

final class ComplexController
{
    public function index(): Response
    {
        $data = [];
        $items = range(1, 10);
        $flag = true;

        foreach ($items as $item) { // +1
            if ($item % 2 === 0 && $flag) { // +2 (if + &&)
                for ($i = 0; $i < 5; $i++) { // +2 (for + nesting)
                    if ($i > 2 || $item < 5) { // +2 (if + || + nesting)
                        try { // +1
                            if ($item === 8) { // +2 (if + nesting)
                                $data[] = $item * $i;
                            }
                        } catch (\Exception $e) { // +1
                            $data[] = 0;
                        }
                    }
                }
            } elseif ($item > 5) { // +1
                $data[] = $item;
            }
        }

        return new Response(json_encode($data));
    }
}
