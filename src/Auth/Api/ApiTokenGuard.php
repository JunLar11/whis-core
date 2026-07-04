<?php

namespace Whis\Auth\Api;

use App\Models\ApiToken;
use Throwable;
use Whis\Http\Request;

class ApiTokenGuard
{
    protected ?ApiAuthContext $context = null;

    public function attempt(Request $request): ApiAuthContext
    {
        $plainToken = $request->bearerToken();

        if ($plainToken === null || $plainToken === '') {
            return $this->context = ApiAuthContext::deny('Token ausente.', 401, 'token');
        }

        try {
            $token = ApiToken::findByPlainTextToken($plainToken);

            if ($token === null || ! $token->isValid()) {
                return $this->context = ApiAuthContext::deny('Token inválido, expirado o revocado.', 401, 'token');
            }

            $user = $token->tokenable();

            if ($user === null) {
                return $this->context = ApiAuthContext::deny('El dueño del token ya no existe.', 401, 'token');
            }

            $token->markAsUsed($request);

            return $this->context = ApiAuthContext::token($user, $token);
        } catch (Throwable $e) {
            return $this->context = ApiAuthContext::deny($e->getMessage(), 401, 'token');
        }
    }

    public function context(): ?ApiAuthContext
    {
        return $this->context;
    }

    public function user(): mixed
    {
        return $this->context?->user;
    }

    public function token(): mixed
    {
        return $this->context?->token;
    }
}
