<?php

declare(strict_types=1);

namespace App\Controller;

use App\Auth\User;
use Firebase\JWT\JWT;
use http\Url;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChatController extends AbstractController
{
    private const string CHANNEL = 'chat';

    public function __construct(
        #[Autowire(env: 'CENTRIFUGO_TOKEN_HMAC_SECRET_KEY')]
        private readonly string $jwtKey,
        #[Autowire(env: 'CENTRIFUGO_BASE_URL')]
        private readonly string $baseUrl,
        #[Autowire(env: 'CENTRIFUGO_API_KEY')]
        private readonly string $apiKey,
        #[Autowire(env: 'CENTRIFUGO_WEBSOCKET_URL')]
        private readonly string $websocketUrl,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    #[Route('/chat', name: 'app_chat', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function __invoke(#[CurrentUser] User $user, Request $request): Response
    {
        if (Request::METHOD_POST === $request->getMethod()) {
            $message = $request->request->get('message');

            $this->sendMessage($user, $message);

            return $this->render('_form.html.twig');
        }

        return $this->render('chat.html.twig', [
            'websocketUrl' => $this->websocketUrl,
            'token' => $this->makeToken($user),
        ]);
    }

    private function sendMessage(User $user, string $message): void
    {
        $url = new Url($this->baseUrl, ['path' => '/api/publish']);

        $data = [
            'channel' => self::CHANNEL,
            'data' => $this->renderView('_message.html.twig', ['user' => $user, 'message' => $message]),
        ];

        $this->httpClient->request('POST', $url->toString(), [
            'headers' => ['X-API-Key' => $this->apiKey],
            'json' => $data,
        ]);
    }

    private function makeToken(User $user): string
    {
        $payload = ['sub' => $user->getUserIdentifier(), 'channels' => [self::CHANNEL]];

        return JWT::encode($payload, $this->jwtKey, 'HS256');
    }
}
