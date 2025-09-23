<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PublicTalkToPrimaMail;
use App\OpenApi\RequestBodies\PublicTalkToPrimaRequestBody;
use App\OpenApi\Responses\MessageResponse;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\RequestBody;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

class PublicTalkToPrimaController extends Controller
{
    #[OpenApi\Operation(
        tags: ['Contact Forms']
    )]
    #[RequestBody(factory: PublicTalkToPrimaRequestBody::class)]
    #[OpenApiResponse(factory: MessageResponse::class)]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var ValidatorContract $validator */
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'string', 'in:Hotel / Property,Concierge,Restaurant,Creator / Influencer,Other'],
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'preferred_contact_time' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $data = $validator->validated();

        $mail = Mail::to(config('forms.notification_emails.to'));

        $ccEmails = config('forms.notification_emails.cc', []);
        if (! empty($ccEmails)) {
            $mail->cc($ccEmails);
        }

        $bccEmails = config('forms.notification_emails.bcc', []);
        if (! empty($bccEmails)) {
            $mail->bcc($bccEmails);
        }

        $mail->send(new PublicTalkToPrimaMail($data));

        // Log successful submission
        logger()->info('Public Talk to PRIMA form submitted', [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'ip' => $request->ip(),
            'referer' => $request->headers->get('Referer'),
        ]);

        return new JsonResponse(['message' => 'Message sent successfully'], 200);
    }
}
