<?php

declare(strict_types=1);

/**
 * Verifiable Credentials Example
 * 
 * Demonstrates:
 * - Creating AgentProfile credentials
 * - Signing credentials
 * - Verifying credentials
 * - Checking expiration
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OAP\OAEP\DID\DIDKey;
use OAP\OAEP\VC\AgentProfile;

echo "\n";
echo "==============================================\n";
echo "  Verifiable Credentials Examples\n";
echo "==============================================\n\n";

// ==================== CREATE ISSUER ====================
echo "📋 Creating Issuer Identity...\n";

$issuerDid = DIDKey::generate();
echo "Issuer DID: " . $issuerDid->toString() . "\n\n";

// ==================== CREATE AGENT PROFILE ====================
echo "📝 Creating AgentProfile Credential...\n";
echo "----------------------------------------------\n";

$agentDid = DIDKey::generate();

$profile = AgentProfile::create([
    'did' => $agentDid,
    'name' => 'FinanceBot AI',
    'type' => AgentProfile::AGENT_TYPE_SERVICE,
    'description' => 'AI agent for financial analysis and reporting',
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0'],
        ['protocol' => 'OAHP', 'version' => '1.0'],
        ['protocol' => 'OAFP', 'version' => '1.0'] // Financial Protocol
    ],
    'issuer' => $issuerDid,
    'expirationDate' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 year'))
]);

echo "Agent Profile (unsigned):\n";
echo $profile->toJson() . "\n\n";

// ==================== SIGN CREDENTIAL ====================
echo "🔏 Signing Credential...\n";
echo "----------------------------------------------\n";

$signedProfile = $profile->sign($issuerDid);

echo "Agent Profile (signed):\n";
echo $signedProfile->toJson() . "\n\n";

// ==================== VERIFY CREDENTIAL ====================
echo "✅ Verifying Credential...\n";
echo "----------------------------------------------\n";

$isValid = $signedProfile->verify($issuerDid);
echo "Verification Result: " . ($isValid ? "✅ Valid" : "❌ Invalid") . "\n";
echo "Issuer: " . $signedProfile->getIssuer() . "\n";
echo "Agent Name: " . $signedProfile->getAgentName() . "\n";
echo "Agent Type: " . $signedProfile->getAgentType() . "\n";
echo "Issued: " . $signedProfile->getIssuanceDate() . "\n";
echo "Expired: " . ($signedProfile->isExpired() ? "❌ Yes" : "✅ No") . "\n\n";

// ==================== PROTOCOL SUPPORT ====================
echo "📡 Checking Protocol Support...\n";
echo "----------------------------------------------\n";

$protocols = $signedProfile->getSupportedProtocols();
echo "Supported Protocols:\n";
foreach ($protocols as $p) {
    echo "  • {$p['protocol']} v{$p['version']}\n";
}
echo "\n";

echo "Supports OACP? " . ($signedProfile->supportsProtocol('OACP') ? "✅ Yes" : "❌ No") . "\n";
echo "Supports OACP v1.0? " . ($signedProfile->supportsProtocol('OACP', '1.0') ? "✅ Yes" : "❌ No") . "\n";
echo "Supports OATP? " . ($signedProfile->supportsProtocol('OATP') ? "✅ Yes" : "❌ No") . "\n\n";

// ==================== SELF-ISSUED CREDENTIAL ====================
echo "📝 Creating Self-Issued Credential...\n";
echo "----------------------------------------------\n";

$selfIssuedProfile = AgentProfile::create([
    'did' => $agentDid,
    'name' => 'Personal AI Assistant',
    'type' => AgentProfile::AGENT_TYPE_PERSONAL,
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0']
    ]
    // No issuer specified - defaults to self-issued
]);

$selfSignedProfile = $selfIssuedProfile->sign($agentDid);
echo "Self-Issued Profile:\n";
echo "  Agent: {$selfSignedProfile->getAgentName()}\n";
echo "  Issuer: {$selfSignedProfile->getIssuer()}\n";
echo "  Subject: {$selfSignedProfile->getAgentDid()}\n";
echo "  Verification: " . ($selfSignedProfile->verify($agentDid) ? "✅ Valid" : "❌ Invalid") . "\n\n";

// ==================== CREDENTIAL TYPES ====================
echo "📋 Different Agent Types...\n";
echo "----------------------------------------------\n";

// Personal Agent
$personalDid = DIDKey::generate();
$personalProfile = AgentProfile::create([
    'did' => $personalDid,
    'name' => "Alice's Personal Assistant",
    'type' => AgentProfile::AGENT_TYPE_PERSONAL,
    'supportedProtocols' => [['protocol' => 'OACP', 'version' => '1.0']]
])->sign($personalDid);

echo "Personal Agent:\n";
echo "  Type: {$personalProfile->getAgentType()}\n";
echo "  Name: {$personalProfile->getAgentName()}\n\n";

// Business Agent
$businessDid = DIDKey::generate();
$businessProfile = AgentProfile::create([
    'did' => $businessDid,
    'name' => 'Acme Corp Sales Agent',
    'type' => AgentProfile::AGENT_TYPE_BUSINESS,
    'description' => 'Automated sales and support agent',
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0'],
        ['protocol' => 'OAHP', 'version' => '1.0']
    ]
])->sign($businessDid);

echo "Business Agent:\n";
echo "  Type: {$businessProfile->getAgentType()}\n";
echo "  Name: {$businessProfile->getAgentName()}\n\n";

// Service Provider Agent
$serviceDid = DIDKey::generate();
$serviceProfile = AgentProfile::create([
    'did' => $serviceDid,
    'name' => 'Weather API Service',
    'type' => AgentProfile::AGENT_TYPE_SERVICE,
    'description' => 'Real-time weather data provider',
    'supportedProtocols' => [['protocol' => 'OAHP', 'version' => '1.0']]
])->sign($serviceDid);

echo "Service Provider Agent:\n";
echo "  Type: {$serviceProfile->getAgentType()}\n";
echo "  Name: {$serviceProfile->getAgentName()}\n\n";

// ==================== JSON SERIALIZATION ====================
echo "💾 JSON Serialization...\n";
echo "----------------------------------------------\n";

$json = $signedProfile->toJson();
echo "Serialized to JSON: " . strlen($json) . " bytes\n";

$deserialized = AgentProfile::fromJson($json);
echo "Deserialized from JSON\n";
echo "  Match: " . ($deserialized->getId() === $signedProfile->getId() ? "✅ Yes" : "❌ No") . "\n";
echo "  Verification: " . ($deserialized->verify($issuerDid) ? "✅ Valid" : "❌ Invalid") . "\n\n";

echo "==============================================\n";
echo "  Verifiable Credentials Examples Complete\n";
echo "==============================================\n\n";
