# OAEP Implementation Guide

## Introduction

This guide provides step-by-step instructions for implementing OAEP in your application. Whether you're building a personal AI assistant, a business agent, or a service provider, this guide will help you integrate OAEP for secure agent-to-agent communication.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Creating an Agent Identity](#creating-an-agent-identity)
3. [Creating an Agent Profile](#creating-an-agent-profile)
4. [Establishing Connections](#establishing-connections)
5. [Hosting a DID Document](#hosting-a-did-document)
6. [Building an OAEP Server](#building-an-oaep-server)
7. [Best Practices](#best-practices)

## Getting Started

### Installation

```bash
composer require oap-foundation/oaep-php
```

### Requirements

- PHP >= 8.0
- Sodium extension (included in PHP 7.2+)
- OpenSSL extension
- HTTPS/TLS 1.3 capable web server

### Basic Setup

```php
<?php
require_once 'vendor/autoload.php';

use OAP\OAEP\DID\DIDKey;
use OAP\OAEP\DID\DIDWeb;
use OAP\OAEP\VC\AgentProfile;
use OAP\OAEP\Handshake\HandshakeManager;
```

## Creating an Agent Identity

### Option 1: Using did:key (Peer-to-Peer)

Best for: Personal agents, temporary agents, maximum decentralization

```php
// Generate a new did:key identity
$did = DIDKey::generate();

echo "Your DID: " . $did->toString() . "\n";
// Output: did:key:z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK

// Get the DID document
$didDocument = $did->resolve();

// Export keys for backup
$publicKey = $did->getPublicKey();
$privateKey = $did->getPrivateKey(); // Keep secure!
```

### Option 2: Using did:web (Organization/Business)

Best for: Businesses, organizations, public services

```php
// Generate a did:web identity for your domain
$did = DIDWeb::generate('example.com');

echo "Your DID: " . $did->toString() . "\n";
// Output: did:web:example.com

// Create the DID document with service endpoints
$serviceEndpoints = [
    [
        'id' => $did->toString() . '#oap',
        'type' => 'OAPEndpoint',
        'serviceEndpoint' => 'https://api.example.com/oap/v1/'
    ]
];

$didDocument = $did->createDocument($serviceEndpoints);

// This document must be hosted at:
// https://example.com/.well-known/did.json
```

### Storing Keys Securely

```php
// NEVER do this in production!
// file_put_contents('private_key.bin', $privateKey);

// Instead, use:
// 1. OS keychain/keyring
// 2. Hardware security module (HSM)
// 3. Encrypted storage with strong password

// Example: Encrypted storage
$password = 'user-password'; // From user input
$salt = random_bytes(32);
$encryptedKey = encryptPrivateKey($privateKey, $password, $salt);
// Store $encryptedKey and $salt securely
```

## Creating an Agent Profile

### Personal Agent

```php
$profile = AgentProfile::create([
    'did' => $did,
    'name' => "Alice's Personal AI",
    'type' => AgentProfile::AGENT_TYPE_PERSONAL,
    'description' => 'Personal assistant for task management',
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0'], // Commerce
        ['protocol' => 'OAHP', 'version' => '1.0']  // Health
    ]
]);

// Sign the profile (self-issued)
$signedProfile = $profile->sign($did);
```

### Business Agent

```php
$profile = AgentProfile::create([
    'did' => $businessDid,
    'name' => 'Acme Corp Sales Agent',
    'type' => AgentProfile::AGENT_TYPE_BUSINESS,
    'description' => 'Automated sales and customer support',
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0']
    ],
    'expirationDate' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 year'))
]);

$signedProfile = $profile->sign($businessDid);
```

### Service Provider Agent

```php
$profile = AgentProfile::create([
    'did' => $serviceDid,
    'name' => 'Weather Data API',
    'type' => AgentProfile::AGENT_TYPE_SERVICE,
    'description' => 'Real-time weather information provider',
    'supportedProtocols' => [
        ['protocol' => 'OAHP', 'version' => '1.0'] // Health/Environmental
    ]
]);

$signedProfile = $profile->sign($serviceDid);
```

## Establishing Connections

### Initiating a Connection (Client Side)

```php
// Your agent's identity and profile
$myDid = DIDKey::generate();
$myProfile = AgentProfile::create([...])->sign($myDid);

// Create handshake manager
$handshake = new HandshakeManager($myDid, $myProfile);

// Step 1: Create connection request
$targetDid = 'did:web:shop.example.com';
$request = $handshake->createConnectionRequest($targetDid);

// Send to remote agent (via HTTP POST)
$response = sendToRemoteAgent($targetDid, '/connect', $request);

// Step 2: Process challenge
$challengeMessage = json_decode($response, true);
$challengeResponse = $handshake->createChallengeResponse($challengeMessage);

// Send response
$finalResponse = sendToRemoteAgent($targetDid, '/verify', $challengeResponse);

// Connection established!
```

### Accepting Connections (Server Side)

```php
// Your server's identity and profile
$serverDid = DIDWeb::generate('shop.example.com');
$serverProfile = AgentProfile::create([...])->sign($serverDid);

// Create handshake manager
$handshake = new HandshakeManager($serverDid, $serverProfile);

// Endpoint: POST /connect
Route::post('/connect', function($request) use ($handshake) {
    $connectionRequest = json_decode($request->body, true);
    
    // Process request and send challenge
    $challenge = $handshake->processConnectionRequest($connectionRequest);
    
    return json_encode($challenge);
});

// Endpoint: POST /verify
Route::post('/verify', function($request) use ($handshake) {
    $response = json_decode($request->body, true);
    
    // Verify the signature
    $isValid = $handshake->verifyConnectionResponse($response);
    
    if ($isValid) {
        $session = $handshake->getSession($response['sessionId']);
        return json_encode([
            'status' => 'connected',
            'sessionId' => $response['sessionId']
        ]);
    }
    
    return json_encode(['error' => 'Invalid signature'], 401);
});
```

## Hosting a DID Document

### Static Hosting (did:web)

1. Create your DID document:

```php
$did = DIDWeb::generate('example.com');
$document = $did->createDocument([
    [
        'id' => 'did:web:example.com#oap',
        'type' => 'OAPEndpoint',
        'serviceEndpoint' => 'https://api.example.com/oap/v1/'
    ]
]);

// Save to file
file_put_contents(
    '/var/www/example.com/.well-known/did.json',
    json_encode($document, JSON_PRETTY_PRINT)
);
```

2. Configure your web server:

**Nginx:**
```nginx
location /.well-known/did.json {
    add_header Content-Type application/json;
    add_header Access-Control-Allow-Origin *;
}
```

3. **Apache:**
```apache
<Location "/.well-known/did.json">
    Header set Content-Type "application/json"
    Header set Access-Control-Allow-Origin "*"
</Location>
```

### Dynamic Hosting (PHP)

```php
// GET /.well-known/did.json
Route::get('/.well-known/did.json', function() {
    $did = loadServerDid(); // Load from secure storage
    $document = $did->resolve();
    
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    echo json_encode($document, JSON_PRETTY_PRINT);
});
```

## Building an OAEP Server

### Complete Example

```php
<?php

require_once 'vendor/autoload.php';

use OAP\OAEP\DID\DIDWeb;
use OAP\OAEP\VC\AgentProfile;
use OAP\OAEP\Handshake\HandshakeManager;

// Initialize server identity
$serverDid = DIDWeb::generate('api.myservice.com');
$serverProfile = AgentProfile::create([
    'did' => $serverDid,
    'name' => 'My Service API',
    'type' => AgentProfile::AGENT_TYPE_SERVICE,
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0']
    ]
])->sign($serverDid);

$handshake = new HandshakeManager($serverDid, $serverProfile);

// API Router
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

if ($method === 'GET' && $uri === '/.well-known/did.json') {
    // Serve DID document
    echo json_encode($serverDid->resolve(), JSON_PRETTY_PRINT);
    exit;
}

if ($method === 'POST' && $uri === '/oap/v1/connect') {
    // Handle connection request
    $request = json_decode(file_get_contents('php://input'), true);
    $challenge = $handshake->processConnectionRequest($request);
    echo json_encode($challenge);
    exit;
}

if ($method === 'POST' && $uri === '/oap/v1/verify') {
    // Handle challenge response
    $response = json_decode(file_get_contents('php://input'), true);
    
    try {
        $isValid = $handshake->verifyConnectionResponse($response);
        
        if ($isValid) {
            echo json_encode([
                'status' => 'connected',
                'sessionId' => $response['sessionId']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
```

## Best Practices

### 1. Key Management

- **Generate keys securely**: Use `sodium_crypto_sign_keypair()` or equivalent
- **Never expose private keys**: Keep them in secure storage
- **Implement key rotation**: Plan for periodic key updates
- **Backup strategies**: Use mnemonic phrases or social recovery

### 2. Session Management

```php
// Clean up expired sessions regularly
$handshake->cleanupExpiredSessions(3600); // 1 hour

// Terminate sessions on logout
$handshake->terminateSession($sessionId);
```

### 3. Error Handling

```php
try {
    $challenge = $handshake->processConnectionRequest($request);
} catch (InvalidArgumentException $e) {
    // Invalid request format
    return error('INVALID_REQUEST', $e->getMessage(), 400);
} catch (RuntimeException $e) {
    // Processing error
    return error('PROCESSING_ERROR', $e->getMessage(), 500);
}
```

### 4. Logging

```php
// DO log
log('Connection request from: ' . $request['from']);
log('Session created: ' . $sessionId);

// DON'T log
// log('Private key: ' . $privateKey); // NEVER!
// log('Challenge: ' . $challenge); // Use hash instead
```

### 5. Testing

```php
// Test handshake flow
$agentA = createTestAgent();
$agentB = createTestAgent();

$request = $agentA->createConnectionRequest($agentB->getDid());
$challenge = $agentB->processConnectionRequest($request);
$response = $agentA->createChallengeResponse($challenge);
$isValid = $agentB->verifyConnectionResponse($response);

assert($isValid === true);
```

## Next Steps

1. **Implement StatusList2021** for credential revocation
2. **Add TLS 1.3 enforcement** in production
3. **Integrate with Layer 1 protocols** (OACP, OAHP, etc.)
4. **Deploy monitoring** and logging
5. **Conduct security audit**

## Resources

- [OAEP Specification v0.1](https://github.com/oap-foundation/oaep-spec/blob/main/specification/v0.1.md)
- [API Documentation](API.md)
- [Security Best Practices](SECURITY.md)
- [Example Implementations](../examples/)

## Support

- GitHub Issues: https://github.com/oap-foundation/oaep-php/issues
- Discussions: https://github.com/oap-foundation/oap-framework/discussions
