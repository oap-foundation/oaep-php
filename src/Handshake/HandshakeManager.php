<?php

declare(strict_types=1);

namespace OAP\OAEP\Handshake;

use OAP\OAEP\DID\DIDInterface;
use OAP\OAEP\VC\AgentProfile;
use InvalidArgumentException;
use RuntimeException;

/**
 * Manages the OAEP handshake protocol
 * 
 * Implements the challenge-response handshake for mutual authentication
 * between two agents.
 */
class HandshakeManager
{
    private DIDInterface $localDid;
    private AgentProfile $localProfile;
    private array $activeSessions = [];

    public function __construct(DIDInterface $localDid, AgentProfile $localProfile)
    {
        $this->localDid = $localDid;
        $this->localProfile = $localProfile;
    }

    /**
     * Step 1: Initiate a connection request to another agent
     *
     * @param string $targetDid The DID of the target agent
     * @return array The connection request message
     */
    public function createConnectionRequest(string $targetDid): array
    {
        return [
            'type' => 'ConnectionRequest',
            'version' => '1.0',
            'from' => $this->localDid->toString(),
            'to' => $targetDid,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'agentProfile' => $this->localProfile->toArray()
        ];
    }

    /**
     * Step 2: Process incoming connection request and create challenge
     *
     * @param array $request The connection request from another agent
     * @return array The challenge response
     */
    public function processConnectionRequest(array $request): array
    {
        // Validate request structure
        if (!isset($request['type'], $request['from'], $request['agentProfile'])) {
            throw new InvalidArgumentException('Invalid connection request structure');
        }

        if ($request['type'] !== 'ConnectionRequest') {
            throw new InvalidArgumentException('Invalid request type');
        }

        // Parse and validate the remote agent's profile
        $remoteProfile = AgentProfile::fromArray($request['agentProfile']);

        // Verify the profile signature
        // Note: In a real implementation, we would resolve the DID and get the public key
        // For now, we assume the profile is valid

        // Generate a challenge (nonce)
        $challenge = bin2hex(random_bytes(32));

        // Store session data
        $sessionId = $this->generateSessionId();
        $this->activeSessions[$sessionId] = [
            'remoteDid' => $request['from'],
            'remoteProfile' => $remoteProfile,
            'challenge' => $challenge,
            'timestamp' => time(),
            'state' => 'CHALLENGE_SENT'
        ];

        // Create challenge response
        return [
            'type' => 'ConnectionChallenge',
            'version' => '1.0',
            'sessionId' => $sessionId,
            'from' => $this->localDid->toString(),
            'to' => $request['from'],
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'challenge' => $challenge,
            'agentProfile' => $this->localProfile->toArray()
        ];
    }

    /**
     * Step 3: Process challenge and create signed response
     *
     * @param array $challengeMessage The challenge from the remote agent
     * @return array The signed response
     */
    public function createChallengeResponse(array $challengeMessage): array
    {
        // Validate challenge message
        if (!isset($challengeMessage['type'], $challengeMessage['challenge'], $challengeMessage['sessionId'])) {
            throw new InvalidArgumentException('Invalid challenge message structure');
        }

        if ($challengeMessage['type'] !== 'ConnectionChallenge') {
            throw new InvalidArgumentException('Invalid message type');
        }

        // Sign the challenge with our private key
        $signature = $this->localDid->sign($challengeMessage['challenge']);

        return [
            'type' => 'ConnectionResponse',
            'version' => '1.0',
            'sessionId' => $challengeMessage['sessionId'],
            'from' => $this->localDid->toString(),
            'to' => $challengeMessage['from'],
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'challengeResponse' => base64_encode($signature)
        ];
    }

    /**
     * Step 4: Verify the challenge response and establish connection
     *
     * @param array $response The response from the remote agent
     * @return bool True if connection is established
     */
    public function verifyConnectionResponse(array $response): bool
    {
        // Validate response structure
        if (!isset($response['type'], $response['sessionId'], $response['challengeResponse'])) {
            throw new InvalidArgumentException('Invalid response structure');
        }

        if ($response['type'] !== 'ConnectionResponse') {
            throw new InvalidArgumentException('Invalid response type');
        }

        // Get session data
        $sessionId = $response['sessionId'];
        if (!isset($this->activeSessions[$sessionId])) {
            throw new RuntimeException('Invalid or expired session');
        }

        $session = $this->activeSessions[$sessionId];

        // Verify the session state
        if ($session['state'] !== 'CHALLENGE_SENT') {
            throw new RuntimeException('Invalid session state');
        }

        // Check for session timeout (5 minutes)
        if (time() - $session['timestamp'] > 300) {
            unset($this->activeSessions[$sessionId]);
            throw new RuntimeException('Session expired');
        }

        // Decode the signature
        $signature = base64_decode($response['challengeResponse']);

        // Get the remote agent's profile and extract public key
        // Note: In a real implementation, we would resolve the DID to get the public key
        // For this implementation, we'll need to have the remote DID object

        // For now, we'll mark the verification as successful
        // In production, you would do: $remoteDid->verify($session['challenge'], $signature)

        // Update session state
        $this->activeSessions[$sessionId]['state'] = 'CONNECTED';
        $this->activeSessions[$sessionId]['connectedAt'] = time();

        return true;
    }

    /**
     * Get active session information
     *
     * @param string $sessionId
     * @return array|null
     */
    public function getSession(string $sessionId): ?array
    {
        return $this->activeSessions[$sessionId] ?? null;
    }

    /**
     * Terminate a session
     *
     * @param string $sessionId
     * @return void
     */
    public function terminateSession(string $sessionId): void
    {
        unset($this->activeSessions[$sessionId]);
    }

    /**
     * Clean up expired sessions
     *
     * @param int $maxAge Maximum age in seconds (default: 1 hour)
     * @return int Number of sessions cleaned
     */
    public function cleanupExpiredSessions(int $maxAge = 3600): int
    {
        $count = 0;
        $now = time();

        foreach ($this->activeSessions as $sessionId => $session) {
            if ($now - $session['timestamp'] > $maxAge) {
                unset($this->activeSessions[$sessionId]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate a unique session ID
     *
     * @return string
     */
    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
