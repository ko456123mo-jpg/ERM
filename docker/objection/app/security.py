"""Fail-closed verification for Laravel-to-tool-gateway service JWTs."""

import os
from pathlib import Path
from typing import Any, Callable

import jwt
from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer

_bearer = HTTPBearer(auto_error=False)


def require_scope(required_scope: str) -> Callable[..., Any]:
    async def verify_service_token(
        credentials: HTTPAuthorizationCredentials | None = Depends(_bearer),
    ) -> dict[str, Any]:
        public_key_path = os.getenv("ERM_JWT_PUBLIC_KEY_PATH", "")
        if not public_key_path or not Path(public_key_path).is_file():
            # Configuration errors fail closed; never fall back to a built-in key.
            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail="Service authentication is not configured.",
            )
        try:
            public_key = Path(public_key_path).read_text(encoding="utf-8")
        except OSError as exc:
            raise HTTPException(status_code=503, detail="Service authentication is not configured.") from exc

        if credentials is None or credentials.scheme.lower() != "bearer":
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Bearer token required.",
                headers={"WWW-Authenticate": "Bearer"},
            )

        if len(credentials.credentials) > 8192:
            raise HTTPException(status_code=401, detail="Invalid service token.")

        try:
            claims = jwt.decode(
                credentials.credentials,
                public_key,
                algorithms=["RS256"],
                issuer=os.getenv("ERM_JWT_ISSUER", "erm-laravel"),
                audience=os.getenv("ERM_JWT_AUDIENCE", "erm-tool-gateway"),
                leeway=2,
                options={"require": ["exp", "iat", "nbf", "iss", "aud", "sub", "jti", "scope"]},
            )
        except jwt.ExpiredSignatureError as exc:
            raise HTTPException(status_code=401, detail="Service token expired.", headers={"WWW-Authenticate": "Bearer"}) from exc
        except jwt.PyJWTError as exc:
            raise HTTPException(status_code=401, detail="Invalid service token.", headers={"WWW-Authenticate": "Bearer"}) from exc
        except (TypeError, ValueError) as exc:
            raise HTTPException(status_code=401, detail="Invalid service token.", headers={"WWW-Authenticate": "Bearer"}) from exc

        issued_at = claims.get("iat")
        expires_at = claims.get("exp")
        if not isinstance(issued_at, int) or not isinstance(expires_at, int) or expires_at <= issued_at or expires_at - issued_at > 60:
            raise HTTPException(status_code=401, detail="Invalid service token lifetime.")

        if claims.get("sub") != "erm-laravel-service":
            raise HTTPException(status_code=401, detail="Invalid service identity.")
        token_id = claims.get("jti")
        if not isinstance(token_id, str) or len(token_id) != 32 or not all(c in "0123456789abcdef" for c in token_id.lower()):
            raise HTTPException(status_code=401, detail="Invalid service token identifier.")

        token_scopes = claims.get("scope", "")
        scopes = token_scopes.split() if isinstance(token_scopes, str) else []
        if required_scope not in scopes:
            raise HTTPException(status_code=403, detail="Insufficient service scope.")

        return claims

    return verify_service_token
