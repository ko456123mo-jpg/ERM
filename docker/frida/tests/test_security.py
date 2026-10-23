import os
import time
import tempfile
from pathlib import Path
from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.hazmat.primitives import serialization
import unittest

import jwt
from fastapi import Depends, FastAPI
from fastapi.testclient import TestClient

from app.security import require_scope


class ServiceJwtTests(unittest.TestCase):
    def setUp(self):
        self.private_key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
        self.public_pem = self.private_key.public_key().public_bytes(
            serialization.Encoding.PEM, serialization.PublicFormat.SubjectPublicKeyInfo
        )
        self.key_file = tempfile.NamedTemporaryFile(delete=False)
        self.key_file.write(self.public_pem)
        self.key_file.close()
        os.environ["ERM_JWT_PUBLIC_KEY_PATH"] = self.key_file.name
        os.environ["ERM_JWT_ISSUER"] = "erm-laravel"
        os.environ["ERM_JWT_AUDIENCE"] = "erm-tool-gateway"
        test_app = FastAPI(docs_url=None, redoc_url=None, openapi_url=None)

        @test_app.get("/protected", dependencies=[Depends(require_scope("analysis:run"))])
        def protected():
            return {"ok": True}

        self.client = TestClient(test_app)

    def token(self, *, scope="analysis:run", secret=None, expires=60):
        now = int(time.time())
        return jwt.encode(
            {
                "iss": "erm-laravel",
                "aud": "erm-tool-gateway",
                "sub": "erm-laravel-service",
                "iat": now,
                "nbf": now,
                "exp": now + expires,
                "jti": "0123456789abcdef0123456789abcdef",
                "scope": scope,
            },
            secret or self.private_key,
            algorithm="RS256",
        )

    def test_missing_token_is_rejected(self):
        self.assertEqual(self.client.get("/protected").status_code, 401)

    def test_invalid_signature_is_rejected(self):
        response = self.client.get("/protected", headers={"Authorization": f"Bearer {self.token(secret=rsa.generate_private_key(public_exponent=65537, key_size=2048))}"})
        self.assertEqual(response.status_code, 401)

    def tearDown(self):
        Path(self.key_file.name).unlink(missing_ok=True)

    def test_expired_token_is_rejected(self):
        response = self.client.get("/protected", headers={"Authorization": f"Bearer {self.token(expires=-5)}"})
        self.assertEqual(response.status_code, 401)

    def test_wrong_scope_is_rejected(self):
        response = self.client.get("/protected", headers={"Authorization": f"Bearer {self.token(scope='devices:read')}"})
        self.assertEqual(response.status_code, 403)

    def test_valid_scoped_token_is_accepted(self):
        response = self.client.get("/protected", headers={"Authorization": f"Bearer {self.token()}"})
        self.assertEqual(response.status_code, 200)
        self.assertEqual(response.json(), {"ok": True})

    def test_missing_public_key_fails_closed(self):
        os.environ["ERM_JWT_PUBLIC_KEY_PATH"] = ""
        response = self.client.get("/protected", headers={"Authorization": f"Bearer {self.token()}"})
        self.assertEqual(response.status_code, 503)


if __name__ == "__main__":
    unittest.main()
