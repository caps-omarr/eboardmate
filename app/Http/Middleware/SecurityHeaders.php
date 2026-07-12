<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ensure the response is an object that can handle headers
        if (method_exists($response, 'headers')) {
            
            // 1. ClickJacking Protection (Prevents hackers from embedding your site in an invisible iframe)
            $response->headers->set('X-Frame-Options', 'DENY');
            
            // 2. Content Type Sniffing (Forces browsers to trust your declared file types)
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            
            // 3. Strict-Transport-Security (Forces browsers to always use HTTPS for the next year)
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            
            // 4. Content-Security-Policy (Restricts where scripts and images can load from)
            // Note: Inertia/Vue requires 'unsafe-inline' for some dynamic styling and script evaluation.
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; img-src 'self' data: blob: https:; font-src 'self' fonts.gstatic.com data:; connect-src 'self' wss: ws: https:; frame-ancestors 'none';");
            
            // Attempt to remove the PHP version header from Laravel's end
            $response->headers->remove('X-Powered-By');
        }

        return $response;
    }
}
