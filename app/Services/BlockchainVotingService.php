<?php

namespace App\Services;

use App\Models\Election;
use App\Models\BlockchainConfig;
use App\Models\BlockchainVoter;
use App\Models\BlockchainVote;
use App\Models\BlockchainTally;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlockchainVotingService
{
    protected ?BlockchainConfig $config = null;

    /**
     * Initialize service for an election
     */
    public function forElection(Election $election): self
    {
        $this->config = $election->blockchainConfig;
        return $this;
    }

    /**
     * Check if blockchain voting is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config?->is_active ?? false;
    }

    /**
     * Register a voter for blockchain voting
     */
    public function registerVoter(User $user, Election $election, string $citizenId): array
    {
        // Hash citizen ID for privacy
        $voterIdHash = hash('sha256', $citizenId . config('app.key'));

        // Check if already registered
        $existing = BlockchainVoter::where('election_id', $election->id)
            ->where('voter_id_hash', $voterIdHash)
            ->first();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'Already registered for this election',
            ];
        }

        // Generate eligibility proof (simplified - real implementation would use ZK proofs)
        $eligibilityProof = $this->generateEligibilityProof($user, $citizenId);

        $voter = BlockchainVoter::create([
            'user_id' => $user->id,
            'election_id' => $election->id,
            'voter_id_hash' => $voterIdHash,
            'eligibility_proof' => $eligibilityProof,
            'is_verified' => false,
        ]);

        return [
            'success' => true,
            'voter_id' => $voter->id,
            'message' => 'Registration submitted. Awaiting verification.',
        ];
    }

    /**
     * Verify voter eligibility
     */
    public function verifyVoter(BlockchainVoter $voter): bool
    {
        // In production, this would verify against official voter registry
        // For now, we auto-verify
        $voter->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return true;
    }

    /**
     * Connect wallet to voter account
     */
    public function connectWallet(BlockchainVoter $voter, string $walletAddress): array
    {
        if (!$voter->is_verified) {
            return [
                'success' => false,
                'message' => 'Voter not verified',
            ];
        }

        // Verify wallet ownership (would require signature verification in production)
        $voter->update(['wallet_address' => $walletAddress]);

        return [
            'success' => true,
            'message' => 'Wallet connected successfully',
        ];
    }

    /**
     * Submit a vote
     */
    public function submitVote(BlockchainVoter $voter, int $candidateId): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Blockchain voting not enabled'];
        }

        if (!$voter->is_verified) {
            return ['success' => false, 'message' => 'Voter not verified'];
        }

        if ($voter->has_voted) {
            return ['success' => false, 'message' => 'Already voted'];
        }

        if (!$voter->wallet_address) {
            return ['success' => false, 'message' => 'Wallet not connected'];
        }

        try {
            // Generate vote commitment (encrypted vote)
            $voteCommitment = $this->encryptVote($candidateId, $voter);

            // Generate nullifier (prevents double voting)
            $nullifier = hash('sha256', $voter->voter_id_hash . $voter->election_id . config('app.key'));

            // Submit to blockchain
            $txHash = $this->submitToBlockchain($voteCommitment, $nullifier, $voter->wallet_address);

            // Record vote
            $vote = BlockchainVote::create([
                'election_id' => $voter->election_id,
                'transaction_hash' => $txHash,
                'vote_commitment' => $voteCommitment,
                'nullifier' => $nullifier,
                'submitted_at' => now(),
                'status' => 'pending',
            ]);

            // Mark voter as voted
            $voter->update([
                'has_voted' => true,
                'voted_at' => now(),
            ]);

            // Log audit
            $this->logAudit($voter->election_id, 'vote_submitted', [
                'transaction_hash' => $txHash,
                'voter_hash' => substr($voter->voter_id_hash, 0, 8) . '...',
            ]);

            return [
                'success' => true,
                'transaction_hash' => $txHash,
                'message' => 'Vote submitted successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Vote submission failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to submit vote: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a vote on blockchain
     */
    public function verifyVote(string $transactionHash): array
    {
        $vote = BlockchainVote::where('transaction_hash', $transactionHash)->first();

        if (!$vote) {
            return ['success' => false, 'message' => 'Vote not found'];
        }

        try {
            // Check transaction on blockchain
            $txData = $this->getTransaction($transactionHash);

            if ($txData['confirmed']) {
                $vote->update([
                    'status' => 'confirmed',
                    'block_number' => $txData['block_number'],
                    'confirmed_at' => now(),
                ]);

                return [
                    'success' => true,
                    'status' => 'confirmed',
                    'block_number' => $txData['block_number'],
                    'timestamp' => $vote->confirmed_at->toIso8601String(),
                ];
            }

            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Transaction pending confirmation',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Tally votes (after election ends)
     */
    public function tallyVotes(Election $election): array
    {
        if (!$this->forElection($election)->isEnabled()) {
            return ['success' => false, 'message' => 'Blockchain voting not enabled'];
        }

        $confirmedVotes = BlockchainVote::where('election_id', $election->id)
            ->where('status', 'confirmed')
            ->get();

        // Decrypt and tally votes
        $tally = [];
        foreach ($confirmedVotes as $vote) {
            $candidateId = $this->decryptVote($vote->vote_commitment);
            $tally[$candidateId] = ($tally[$candidateId] ?? 0) + 1;
        }

        // Store tally results
        foreach ($tally as $candidateId => $votes) {
            BlockchainTally::updateOrCreate(
                [
                    'election_id' => $election->id,
                    'candidate_id' => $candidateId,
                ],
                [
                    'votes' => $votes,
                    'is_verified' => true,
                ]
            );
        }

        // Generate Merkle root for verification
        $merkleRoot = $this->generateMerkleRoot(array_values($tally));

        $this->logAudit($election->id, 'tally_completed', [
            'total_votes' => array_sum($tally),
            'merkle_root' => $merkleRoot,
        ]);

        return [
            'success' => true,
            'total_votes' => array_sum($tally),
            'results' => $tally,
            'merkle_root' => $merkleRoot,
        ];
    }

    /**
     * Get voting statistics
     */
    public function getStats(Election $election): array
    {
        $totalRegistered = BlockchainVoter::where('election_id', $election->id)->count();
        $verified = BlockchainVoter::where('election_id', $election->id)->verified()->count();
        $voted = BlockchainVoter::where('election_id', $election->id)->where('has_voted', true)->count();
        $confirmedVotes = BlockchainVote::where('election_id', $election->id)->confirmed()->count();
        $pendingVotes = BlockchainVote::where('election_id', $election->id)->pending()->count();

        return [
            'total_registered' => $totalRegistered,
            'verified_voters' => $verified,
            'votes_cast' => $voted,
            'confirmed_on_chain' => $confirmedVotes,
            'pending_confirmation' => $pendingVotes,
            'participation_rate' => $verified > 0 ? round(($voted / $verified) * 100, 2) : 0,
        ];
    }

    /**
     * Generate eligibility proof (simplified)
     */
    protected function generateEligibilityProof(User $user, string $citizenId): string
    {
        // In production, this would generate a ZK proof
        return base64_encode(json_encode([
            'user_id' => $user->id,
            'citizen_hash' => hash('sha256', $citizenId),
            'timestamp' => now()->timestamp,
            'nonce' => Str::random(32),
        ]));
    }

    /**
     * Encrypt vote
     */
    protected function encryptVote(int $candidateId, BlockchainVoter $voter): string
    {
        // In production, this would use proper encryption (e.g., ElGamal or Paillier)
        $key = hash('sha256', $voter->voter_id_hash . $this->config->contract_address);
        $iv = substr(hash('sha256', $voter->nullifier ?? ''), 0, 16);

        $encrypted = openssl_encrypt(
            json_encode(['candidate_id' => $candidateId, 'timestamp' => now()->timestamp]),
            'AES-256-CBC',
            $key,
            0,
            $iv
        );

        return base64_encode($encrypted);
    }

    /**
     * Decrypt vote
     */
    protected function decryptVote(string $commitment): int
    {
        // In production, this would use proper decryption with election keys
        // For demo, we'll just return the candidate ID from a lookup
        // Real implementation would need the election private key

        return 1; // Placeholder
    }

    /**
     * Submit transaction to blockchain
     */
    protected function submitToBlockchain(string $commitment, string $nullifier, string $wallet): string
    {
        if (!$this->config) {
            throw new \Exception('Blockchain not configured');
        }

        // In production, this would:
        // 1. Build the transaction
        // 2. Sign with user's wallet (via MetaMask or similar)
        // 3. Submit to the blockchain

        // For demo, simulate with HTTP call
        $response = Http::timeout(30)
            ->post($this->config->rpc_endpoint, [
                'jsonrpc' => '2.0',
                'method' => 'eth_sendRawTransaction',
                'params' => [
                    // Transaction data would go here
                ],
                'id' => 1,
            ]);

        // Return mock transaction hash for demo
        return '0x' . hash('sha256', $commitment . $nullifier . time());
    }

    /**
     * Get transaction from blockchain
     */
    protected function getTransaction(string $txHash): array
    {
        if (!$this->config) {
            throw new \Exception('Blockchain not configured');
        }

        // In production, this would query the blockchain
        // For demo, simulate confirmation
        return [
            'confirmed' => true,
            'block_number' => rand(1000000, 9999999),
        ];
    }

    /**
     * Generate Merkle root
     */
    protected function generateMerkleRoot(array $values): string
    {
        if (empty($values)) {
            return hash('sha256', '');
        }

        $hashes = array_map(fn($v) => hash('sha256', (string) $v), $values);

        while (count($hashes) > 1) {
            $newHashes = [];
            for ($i = 0; $i < count($hashes); $i += 2) {
                $left = $hashes[$i];
                $right = $hashes[$i + 1] ?? $left;
                $newHashes[] = hash('sha256', $left . $right);
            }
            $hashes = $newHashes;
        }

        return $hashes[0];
    }

    /**
     * Log audit event
     */
    protected function logAudit(int $electionId, string $action, array $details = []): void
    {
        \App\Models\BlockchainAuditLog::create([
            'election_id' => $electionId,
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
