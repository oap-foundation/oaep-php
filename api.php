<?php

/**
 * OAEP Demo API
 * 
 * Provides endpoints for the web demo interface
 */

// For demo purposes, we'll include the classes directly
// In production, use Composer autoloader

// Simple autoloader
spl_autoload_register(function ($class) {
    $prefix = 'OAP\\OAEP\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use OAP\OAEP\DID\DIDKey;
use OAP\OAEP\DID\DIDWeb;
use OAP\OAEP\VC\AgentProfile;
use OAP\OAEP\Handshake\HandshakeManager;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'generate_did':
            $type = $_GET['type'] ?? 'key';

            if ($type === 'key') {
                $did = DIDKey::generate();
            } else {
                $did = DIDWeb::generate('example.com');
            }

            echo json_encode([
                'success' => true,
                'did' => $did->toString(),
                'document' => $did->resolve()
            ]);
            break;

        case 'create_profile':
            $agentType = $_GET['agent_type'] ?? 'personal';

            $did = DIDKey::generate();

            $names = [
                'personal' => "Alice's Personal AI",
                'business' => 'Acme Corp Agent',
                'service' => 'Weather API Service'
            ];

            $types = [
                'personal' => AgentProfile::AGENT_TYPE_PERSONAL,
                'business' => AgentProfile::AGENT_TYPE_BUSINESS,
                'service' => AgentProfile::AGENT_TYPE_SERVICE
            ];

            $profile = AgentProfile::create([
                'did' => $did,
                'name' => $names[$agentType] ?? $names['personal'],
                'type' => $types[$agentType] ?? $types['personal'],
                'supportedProtocols' => [
                    ['protocol' => 'OACP', 'version' => '1.0']
                ]
            ]);

            $signedProfile = $profile->sign($did);

            echo json_encode([
                'success' => true,
                'profile' => $signedProfile->toArray()
            ]);
            break;

        case 'handshake_demo':
            // Create Agent A
            $agentA_DID = DIDKey::generate();
            $agentA_Profile = AgentProfile::create([
                'did' => $agentA_DID,
                'name' => "Alice's Personal AI",
                'type' => AgentProfile::AGENT_TYPE_PERSONAL,
                'supportedProtocols' => [
                    ['protocol' => 'OACP', 'version' => '1.0']
                ]
            ])->sign($agentA_DID);

            $handshakeA = new HandshakeManager($agentA_DID, $agentA_Profile);

            // Create Agent B
            $agentB_DID = DIDKey::generate();
            $agentB_Profile = AgentProfile::create([
                'did' => $agentB_DID,
                'name' => "Bob's Shop Agent",
                'type' => AgentProfile::AGENT_TYPE_SERVICE,
                'supportedProtocols' => [
                    ['protocol' => 'OACP', 'version' => '1.0']
                ]
            ])->sign($agentB_DID);

            $handshakeB = new HandshakeManager($agentB_DID, $agentB_Profile);

            // Perform handshake
            $steps = [];

            // Step 1: Connection Request
            $connectionRequest = $handshakeA->createConnectionRequest($agentB_DID->toString());
            $steps[] = [
                'title' => 'Agent A → Agent B: ConnectionRequest',
                'details' => 'Agent A sendet seine Identität und Credentials'
            ];

            // Step 2: Connection Challenge
            $challengeMessage = $handshakeB->processConnectionRequest($connectionRequest);
            $steps[] = [
                'title' => 'Agent B → Agent A: ConnectionChallenge',
                'details' => 'Agent B erstellt eine kryptographische Challenge: ' . substr($challengeMessage['challenge'], 0, 16) . '...'
            ];

            // Step 3: Challenge Response
            $challengeResponse = $handshakeA->createChallengeResponse($challengeMessage);
            $steps[] = [
                'title' => 'Agent A → Agent B: ConnectionResponse',
                'details' => 'Agent A signiert die Challenge mit seinem privaten Schlüssel'
            ];

            // Step 4: Verification
            $session = $handshakeB->getSession($challengeResponse['sessionId']);
            $challenge = $session['challenge'];
            $signatureDecoded = base64_decode($challengeResponse['challengeResponse']);
            $isValid = $agentA_DID->verify($challenge, $signatureDecoded);

            $steps[] = [
                'title' => 'Agent B: Signatur Verifizierung',
                'details' => $isValid ? '✅ Signatur erfolgreich verifiziert!' : '❌ Verifizierung fehlgeschlagen'
            ];

            echo json_encode([
                'success' => $isValid,
                'steps' => $steps,
                'sessionId' => $challengeResponse['sessionId'],
                'agentA' => $agentA_Profile->getAgentName(),
                'agentB' => $agentB_Profile->getAgentName()
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action'
            ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
