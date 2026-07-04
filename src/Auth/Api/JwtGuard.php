<?php

namespace Whis\Auth\Api;

use Throwable;
use Whis\Auth\Authenticatable;
use Whis\Http\Request;

class JwtGuard
{
    protected ?ApiAuthContext $context = null;

    public function attempt(Request $request): ApiAuthContext
    {
        $jwt = $request->bearerToken();

        if ($jwt === null || substr_count($jwt, '.') !== 2) {
            return $this->context = ApiAuthContext::deny('JWT ausente.', 401, 'jwt');
        }

        try {
            $payload = Jwt::decode($jwt);

            $model = $payload['model']
                ?? $payload['tokenable_type']
                ?? config('api.auth.user_model')
                ?? 'App\\Models\\User';

            if (! is_string($model) || ! class_exists($model) || ! is_subclass_of($model, Authenticatable::class)) {
                return $this->context = ApiAuthContext::deny('Modelo autenticable JWT inválido.', 401, 'jwt');
            }

            $subject = $payload['sub'] ?? null;

            if ($subject === null || $subject === '') {
                return $this->context = ApiAuthContext::deny('JWT sin sujeto.', 401, 'jwt');
            }

            $user = $model::find($subject);

            if ($user === null) {
                return $this->context = ApiAuthContext::deny('Usuario JWT no encontrado.', 401, 'jwt');
            }

            return $this->context = ApiAuthContext::jwt($user, $payload);
        } catch (Throwable $e) {
            return $this->context = ApiAuthContext::deny($e->getMessage(), 401, 'jwt');
        }
    }

    public function context(): ?ApiAuthContext
    {
        return $this->context;
    }
}
