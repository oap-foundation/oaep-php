<?php

declare(strict_types=1);

/**
 * DID Generation and Resolution Example
 * 
 * Demonstrates:
 * - Creating did:key identities
 * - Creating did:web identities
 * - Resolving DID documents
 * - Signing and verifying with DIDs
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OAP\OAEP\DID\DIDKey;
use OAP\OAEP\DID\DIDWeb;

echo "\n";
echo "==============================================\n";
echo "  DID (Decentralized Identifier) Examples\n";
echo "==============================================\n\n";

// ==================== DID:KEY EXAMPLE ====================
echo "📌 Example 1: did:key (Key-based DID)\n";
echo "----------------------------------------------\n";

$didKey = DIDKey::generate();
echo "Generated DID: " . $didKey->toString() . "\n\n";

echo "DID Document:\n";
$didDocument = $didKey->resolve();
echo json_encode($didDocument, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Test signing and verification
$message = "Hello, OAEP!";
$signature = $didKey->sign($message);
echo "Signed message: \"$message\"\n";
echo "Signature: " . bin2hex($signature) . "\n";

$isValid = $didKey->verify($message, $signature);
echo "Verification: " . ($isValid ? "✅ Valid" : "❌ Invalid") . "\n\n";

// Test with wrong message
$wrongMessage = "Hello, World!";
$isValidWrong = $didKey->verify($wrongMessage, $signature);
echo "Verification with wrong message: " . ($isValidWrong ? "✅ Valid" : "❌ Invalid (expected)") . "\n\n";

// ==================== DID:WEB EXAMPLE ====================
echo "📌 Example 2: did:web (Web-based DID)\n";
echo "----------------------------------------------\n";

$didWeb = DIDWeb::generate('example.com');
echo "Generated DID: " . $didWeb->toString() . "\n\n";

// Create DID document with service endpoint
$serviceEndpoints = [
    [
        'id' => $didWeb->toString() . '#oap',
        'type' => 'OAPEndpoint',
        'serviceEndpoint' => 'https://api.example.com/oap/v1/'
    ]
];

$webDocument = $didWeb->createDocument($serviceEndpoints);
echo "DID Document:\n";
echo json_encode($webDocument, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "💡 This document should be hosted at:\n";
echo "   https://example.com/.well-known/did.json\n\n";

// ==================== DID:WEB WITH PATH ====================
echo "📌 Example 3: did:web with path\n";
echo "----------------------------------------------\n";

$didWebPath = DIDWeb::generate('company.com', '/users/alice');
echo "Generated DID: " . $didWebPath->toString() . "\n";
echo "Document URL: https://company.com/users/alice/did.json\n\n";

// ==================== PARSING DID STRINGS ====================
echo "📌 Example 4: Parsing DID strings\n";
echo "----------------------------------------------\n";

$didString1 = "did:key:z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK";
echo "Parsing: $didString1\n";

try {
    $parsedDid = DIDKey::fromString($didString1);
    echo "  Method: " . $parsedDid->getMethod() . "\n";
    echo "  Method-specific ID: " . $parsedDid->getMethodSpecificId() . "\n";
    echo "  ✅ Successfully parsed\n\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

$didString2 = "did:web:example.com";
echo "Parsing: $didString2\n";

try {
    $parsedWeb = DIDWeb::fromString($didString2);
    echo "  Method: " . $parsedWeb->getMethod() . "\n";
    echo "  Domain: " . $parsedWeb->getDomain() . "\n";
    echo "  ✅ Successfully parsed\n\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

// ==================== KEY EXPORT/IMPORT ====================
echo "📌 Example 5: Key Export/Import\n";
echo "----------------------------------------------\n";

$originalDid = DIDKey::generate();
echo "Original DID: " . $originalDid->toString() . "\n";

// Export key material
$publicKey = $originalDid->getPublicKey();
$privateKey = $originalDid->getPrivateKey();

// Recreate from key material
$recreatedDid = DIDKey::fromKeyMaterial([
    'publicKey' => $publicKey,
    'privateKey' => $privateKey
]);

echo "Recreated DID: " . $recreatedDid->toString() . "\n";
echo "Match: " . ($originalDid->toString() === $recreatedDid->toString() ? "✅ Yes" : "❌ No") . "\n\n";

echo "==============================================\n";
echo "  DID Examples Complete\n";
echo "==============================================\n\n";
