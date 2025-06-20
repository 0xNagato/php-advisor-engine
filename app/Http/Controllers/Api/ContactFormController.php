<?php

namespace App\Http\Controllers\Api;

use App\Actions\SendContactFormEmail;
use App\Http\Controllers\Controller;
use App\OpenApi\RequestBodies\ContactFormRequestBody;
use App\OpenApi\Responses\MessageResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\RequestBody;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class ContactFormController extends Controller
{
    /**
     * Submit a contact form.
     *
     * Validates the request and sends the contact form data via email.
     */
    #[OpenApi\Operation(
        tags: ['Contact Forms'],
        security: 'BearerTokenSecurityScheme'
    )]
    #[RequestBody(factory: ContactFormRequestBody::class)]
    #[OpenApiResponse(factory: MessageResponse::class)]
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $data = $request->only('message');

        SendContactFormEmail::run($data, $user);

        return response()->json(['message' => 'Message sent successfully'], 200);
    }
}
