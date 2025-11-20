# OAEP API Documentation

## Overview

The OAEP API provides REST endpoints for establishing secure, authenticated connections between agents using the Open Agent Exchange Protocol.

## Base URL

```
https://your-domain.com/oap/v1/
```

## Authentication

All requests must be sent over HTTPS (TLS 1.3+). Authentication is handled through the OAEP handshake protocol using DIDs and Verifiable Credentials.

## Endpoints

### 1. Connection Request

Initiates a connection with an agent.

**Endpoint:** `POST /connect`

**Request Body:**
```json
{
  "type": "ConnectionRequest",
  "version": "1.0",
  "from": "did:key:z6Mkf...",
  "to": "did:web:example.com",
  "timestamp": "2026-01-15T10:00:00Z",
  "agentProfile": {
    "@context": [...],
    "type": ["VerifiableCredential", "AgentProfile"],
    ...
  }
}
```

**Response:** `200 OK`
```json
{
  "type": "ConnectionChallenge",
  "version": "1.0",
  "sessionId": "a1b2c3d4...",
  "from": "did:web:example.com",
  "to": "did:key:z6Mkf...",
  "timestamp": "2026-01-15T10:00:01Z",
  "challenge": "3f8a2c1d...",
  "agentProfile": {
    ...
  }
}
```

### 2. Challenge Response

Responds to a connection challenge with a signed response.

**Endpoint:** `POST /verify`

**Request Body:**
```json
{
  "type": "ConnectionResponse",
  "version": "1.0",
  "sessionId": "a1b2c3d4...",
  "from": "did:key:z6Mkf...",
  "to": "did:web:example.com",
  "timestamp": "2026-01-15T10:00:02Z",
  "challengeResponse": "base64-encoded-signature"
}
```

**Response:** `200 OK`
```json
{
  "status": "connected",
  "sessionId": "a1b2c3d4...",
  "connectedAt": "2026-01-15T10:00:02Z"
}
```

### 3. DID Document

Retrieve the DID document for a did:web identity.

**Endpoint:** `GET /.well-known/did.json`

**Response:** `200 OK`
```json
{
  "@context": [
    "https://www.w3.org/ns/did/v1",
    "https://w3id.org/security/suites/ed25519-2020/v1"
  ],
  "id": "did:web:example.com",
  "verificationMethod": [{
    "id": "did:web:example.com#key-1",
    "type": "Ed25519VerificationKey2020",
    "controller": "did:web:example.com",
    "publicKeyMultibase": "z6Mkf..."
  }],
  "authentication": ["did:web:example.com#key-1"],
  "assertionMethod": ["did:web:example.com#key-1"],
  "service": [{
    "id": "did:web:example.com#oap",
    "type": "OAPEndpoint",
    "serviceEndpoint": "https://api.example.com/oap/v1/"
  }]
}
```

## Error Responses

All errors follow this format:

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {}
  }
}
```

### Common Error Codes

- `INVALID_REQUEST` - Malformed request body
- `INVALID_DID` - DID format is invalid
- `INVALID_SIGNATURE` - Signature verification failed
- `SESSION_EXPIRED` - The session has expired
- `SESSION_NOT_FOUND` - Invalid session ID
- `UNSUPPORTED_PROTOCOL` - Protocol version not supported

## Message Types

### ConnectionRequest

Initiates a connection.

**Required Fields:**
- `type`: "ConnectionRequest"
- `version`: "1.0"
- `from`: Sender's DID
- `to`: Recipient's DID
- `timestamp`: ISO 8601 timestamp
- `agentProfile`: Verifiable Credential

### ConnectionChallenge

Response with a challenge to prove identity.

**Required Fields:**
- `type`: "ConnectionChallenge"
- `version`: "1.0"
- `sessionId`: Unique session identifier
- `from`: Sender's DID
- `to`: Recipient's DID
- `timestamp`: ISO 8601 timestamp
- `challenge`: Random nonce to be signed
- `agentProfile`: Verifiable Credential

### ConnectionResponse

Signed challenge response.

**Required Fields:**
- `type`: "ConnectionResponse"
- `version`: "1.0"
- `sessionId`: Session identifier from challenge
- `from`: Sender's DID
- `to`: Recipient's DID
- `timestamp`: ISO 8601 timestamp
- `challengeResponse`: Base64-encoded signature

## Security Considerations

1. **TLS Required**: All communication MUST use TLS 1.3 or higher
2. **Challenge Expiration**: Challenges expire after 5 minutes
3. **Session Timeout**: Sessions expire after 1 hour of inactivity
4. **Replay Protection**: Each challenge is single-use
5. **DID Verification**: Always verify the DID document and credential signatures

## Rate Limiting

- Connection requests: 10 per minute per IP
- Challenge responses: 20 per minute per IP

## Examples

See the `/examples` directory for complete implementation examples:
- `simple-handshake.php` - Complete handshake flow
- `did-examples.php` - DID creation and resolution
- `vc-examples.php` - Verifiable Credential examples
