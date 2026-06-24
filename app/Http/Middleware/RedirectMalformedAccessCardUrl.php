<?php

namespace App\Http\Middleware;

use App\Services\AccessCard\MalformedAccessCardUrlResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMalformedAccessCardUrl
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $corrected = MalformedAccessCardUrlResolver::resolve($request->getRequestUri());

        if ($corrected !== null) {
            return redirect()->to($corrected, 302);
        }

        return $next($request);
    }
}
