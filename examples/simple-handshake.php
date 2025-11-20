<?php

declare(strict_types=1);

/**
 * Simple demonstration of OAEP handshake between two agents
 * 
 * This example shows the complete flow:
 * 1. Agent A generates identity and creates profile
 * 2. Agent B generates identity and creates profile
 * 3. Agent A initiates connection to Agent B
 * 4. Agent B receives request and sends challenge
 * 5. Agent A signs challenge and responds
 * 6. Agent B verifies signature and establishes connection
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OAP\OAEP\DID\DIDKey;
use OAP\OAEP\VC\AgentProfile;
use OAP\OAEP\Handshake\HandshakeManager;

echo "\n";
echo "==============================================\n";
echo "  OAEP Handshake Demonstration\n";
echo "==============================================\n\n";

// ==================== AGENT A SETUP ====================
echo "📋 AGENT A: Setting up identity...\n";

$agentA_DID = DIDKey::generate();
echo "   DID: " . $agentA_DID->toString() . "\n";

$agentA_Profile = AgentProfile::create([
    'did' => $agentA_DID,
    'name' => "Alice's Personal AI",
    'type' => AgentProfile::AGENT_TYPE_PERSONAL,
    'description' => 'Personal assistant for Alice',
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0'],
        ['protocol' => 'OAHP', 'version' => '1.0']
    ]
]);

// Sign the profile (self-issued)
$agentA_Profile = $agentA_Profile->sign($agentA_DID);
echo "   ✅ Profile created and signed\n\n";

// Create handshake manager for Agent A
$handshakeA = new HandshakeManager($agentA_DID, $agentA_Profile);

// ==================== AGENT B SETUP ====================
echo "📋 AGENT B: Setting up identity...\n";

$agentB_DID = DIDKey::generate();
echo "   DID: " . $agentB_DID->toString() . "\n";

$agentB_Profile = AgentProfile::create([
    'did' => $agentB_DID,
    'name' => "Bob's Shop Agent",
    'type' => AgentProfile::AGENT_TYPE_SERVICE,
    'description' => 'E-commerce service agent',
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0']
    ]
]);

// Sign the profile (self-issued)
$agentB_Profile = $agentB_Profile->sign($agentB_DID);
echo "   ✅ Profile created and signed\n\n";

// Create handshake manager for Agent B
$handshakeB = new HandshakeManager($agentB_DID, $agentB_Profile);

// ==================== HANDSHAKE FLOW ====================
echo "🤝 Starting OAEP Handshake...\n\n";

// STEP 1: Agent A creates connection request
echo "1️⃣  Agent A → Agent B: ConnectionRequest\n";
$connectionRequest = $handshakeA->createConnectionRequest($agentB_DID->toString());
echo "    From: " . $connectionRequest['from'] . "\n";
echo "    To: " . $connectionRequest['to'] . "\n";
echo "    Agent Name: " . $connectionRequest['agentProfile']['credentialSubject']['agent']['name'] . "\n\n";

// STEP 2: Agent B receives request and creates challenge
echo "2️⃣  Agent B → Agent A: ConnectionChallenge\n";
$challengeMessage = $handshakeB->processConnectionRequest($connectionRequest);
echo "    Session ID: " . $challengeMessage['sessionId'] . "\n";
echo "    Challenge: " . substr($challengeMessage['challenge'], 0, 16) . "...\n";
echo "    Agent Name: " . $challengeMessage['agentProfile']['credentialSubject']['agent']['name'] . "\n\n";

// STEP 3: Agent A signs the challenge and responds
echo "3️⃣  Agent A → Agent B: ConnectionResponse (signed challenge)\n";
$challengeResponse = $handshakeA->createChallengeResponse($challengeMessage);
echo "    Session ID: " . $challengeResponse['sessionId'] . "\n";
echo "    Signature: " . substr($challengeResponse['challengeResponse'], 0, 20) . "...\n\n";

// STEP 4: Agent B verifies the signature
echo "4️⃣  Agent B: Verifying signature...\n";

// Manual verification (since we have the DID object)
$session = $handshakeB->getSession($challengeResponse['sessionId']);
$challenge = $session['challenge'];
$signatureDecoded = base64_decode($challengeResponse['challengeResponse']);
$isValid = $agentA_DID->verify($challenge, $signatureDecoded);

if ($isValid) {
    echo "    ✅ Signature verified successfully!\n";
    echo "    ✅ Connection established!\n\n";

    // Complete the handshake
    $handshakeB->verifyConnectionResponse($challengeResponse);

    // Show session info
    $session = $handshakeB->getSession($challengeResponse['sessionId']);
    echo "📊 Connection Details:\n";
    echo "    Session ID: " . $challengeResponse['sessionId'] . "\n";
    echo "    Remote Agent: " . $session['remoteProfile']->getAgentName() . "\n";
    echo "    Remote DID: " . $session['remoteDid'] . "\n";
    echo "    State: " . $session['state'] . "\n";
    echo "    Connected at: " . date('Y-m-d H:i:s', $session['connectedAt']) . "\n";
} else {
    echo "    ❌ Signature verification failed!\n";
}

echo "\n";
echo "==============================================\n";
echo "  Agent Communication Now Secured! 🔐\n";
echo "==============================================\n";
echo "\n";

// Show what protocols both agents can use
echo "📡 Shared Protocols:\n";
$protocolsA = array_column($agentA_Profile->getSupportedProtocols(), 'protocol');
$protocolsB = array_column($agentB_Profile->getSupportedProtocols(), 'protocol');
$shared = array_intersect($protocolsA, $protocolsB);

foreach ($shared as $protocol) {
    echo "    ✅ Both agents support: $protocol\n";
}

echo "\n";
echo "💡 Next Steps:\n";
echo "   • Agents can now use OACP for commerce transactions\n";
echo "   • All communication is mutually authenticated\n";
echo "   • Identities are cryptographically verified\n";
echo "\n";
