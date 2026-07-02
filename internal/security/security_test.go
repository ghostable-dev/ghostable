package security

import (
	"bytes"
	"crypto/ed25519"
	"encoding/base64"
	"encoding/json"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/beta/internal/domain"
)

type securityVectors struct {
	Version       string `json:"version"`
	CanonicalJSON struct {
		Input    map[string]interface{} `json:"input"`
		Expected string                 `json:"expected"`
	} `json:"canonicalJSON"`
	HKDF struct {
		MasterB64        string `json:"masterB64"`
		Scope            string `json:"scope"`
		EncryptionKeyB64 string `json:"encryptionKeyB64"`
		HMACKeyB64       string `json:"hmacKeyB64"`
	} `json:"hkdf"`
	XChaCha20Poly1305 struct {
		KeyB64        string `json:"keyB64"`
		NonceB64      string `json:"nonceB64"`
		AADB64        string `json:"aadB64"`
		PlaintextB64  string `json:"plaintextB64"`
		CiphertextB64 string `json:"ciphertextB64"`
	} `json:"xchacha20poly1305"`
	Ed25519CanonicalSignature struct {
		SeedB64      string `json:"seedB64"`
		PublicKeyB64 string `json:"publicKeyB64"`
		Canonical    string `json:"canonical"`
		SignatureB64 string `json:"signatureB64"`
	} `json:"ed25519CanonicalSignature"`
}

func TestSecurityTestVectors(t *testing.T) {
	vectors := loadSecurityVectors(t)
	if vectors.Version != "ghostable.security-test-vectors.v1" {
		t.Fatalf("unexpected vector version %q", vectors.Version)
	}

	canonical, err := CanonicalJSON(vectors.CanonicalJSON.Input)
	if err != nil {
		t.Fatal(err)
	}
	if canonical != vectors.CanonicalJSON.Expected {
		t.Fatalf("canonical JSON mismatch\nexpected: %s\nactual:   %s", vectors.CanonicalJSON.Expected, canonical)
	}

	master := mustB64(t, vectors.HKDF.MasterB64)
	encKey, hmacKey, err := DeriveValueKeys(master, vectors.HKDF.Scope)
	if err != nil {
		t.Fatal(err)
	}
	if got := B64(encKey); got != vectors.HKDF.EncryptionKeyB64 {
		t.Fatalf("derived encryption key mismatch: %s", got)
	}
	if got := B64(hmacKey); got != vectors.HKDF.HMACKeyB64 {
		t.Fatalf("derived HMAC key mismatch: %s", got)
	}

	plaintext, err := DecryptXChaCha(
		mustB64(t, vectors.XChaCha20Poly1305.KeyB64),
		domain.EncryptedPayload{
			Alg:           CipherAlg,
			NonceB64:      vectors.XChaCha20Poly1305.NonceB64,
			CiphertextB64: vectors.XChaCha20Poly1305.CiphertextB64,
		},
		mustB64(t, vectors.XChaCha20Poly1305.AADB64),
	)
	if err != nil {
		t.Fatal(err)
	}
	if !bytes.Equal(plaintext, mustB64(t, vectors.XChaCha20Poly1305.PlaintextB64)) {
		t.Fatalf("plaintext mismatch: %q", plaintext)
	}
	if _, err := DecryptXChaCha(
		mustB64(t, vectors.XChaCha20Poly1305.KeyB64),
		domain.EncryptedPayload{
			Alg:           CipherAlg,
			NonceB64:      vectors.XChaCha20Poly1305.NonceB64,
			CiphertextB64: vectors.XChaCha20Poly1305.CiphertextB64,
		},
		[]byte("wrong aad"),
	); err == nil {
		t.Fatal("expected documented XChaCha vector to reject wrong AAD")
	}

	signatureIdentity := domain.LocalIdentityRecord{
		Schema:               domain.LocalIdentitySchema,
		ProjectID:            "project-fixture",
		DeviceID:             "dev_fixture",
		SigningPublicKeyB64:  vectors.Ed25519CanonicalSignature.PublicKeyB64,
		SigningPrivateKeyB64: vectors.Ed25519CanonicalSignature.SeedB64,
	}
	signature, err := SignCanonical(map[string]string{
		"schema": "ghostable.test.v1",
		"name":   "fixture",
	}, signatureIdentity)
	if err != nil {
		t.Fatal(err)
	}
	if signature != vectors.Ed25519CanonicalSignature.SignatureB64 {
		t.Fatalf("signature mismatch: %s", signature)
	}
	verifyValue := map[string]interface{}{
		"schema":     "ghostable.test.v1",
		"name":       "fixture",
		"device_id":  "dev_fixture",
		"client_sig": "ignored",
	}
	if !VerifyCanonical(verifyValue, vectors.Ed25519CanonicalSignature.PublicKeyB64, signature) {
		t.Fatal("expected fixture signature to verify")
	}
	verifyValue["name"] = "tampered"
	if VerifyCanonical(verifyValue, vectors.Ed25519CanonicalSignature.PublicKeyB64, signature) {
		t.Fatal("expected fixture signature to reject tampered canonical value")
	}
}

func TestDeviceIdentityBindingFailsClosed(t *testing.T) {
	identity, device := mustDeviceIdentity(t, "project-1", "Laptop")
	if err := VerifyDeviceRecord(device); err != nil {
		t.Fatal(err)
	}
	if device.ID != identity.DeviceID {
		t.Fatalf("device ID is not bound to identity: %s != %s", device.ID, identity.DeviceID)
	}

	tampered := device
	tampered.ID = "dev_tampered"
	if err := VerifyDeviceRecord(tampered); err == nil || !strings.Contains(err.Error(), "bound") {
		t.Fatalf("expected tampered device ID to fail binding, got %v", err)
	}

	tampered = device
	tampered.Status = "revoked"
	if err := VerifyDeviceRecord(tampered); err == nil || !strings.Contains(err.Error(), "not active") {
		t.Fatalf("expected inactive device to fail, got %v", err)
	}

	tampered = device
	tampered.ClientSig = ""
	if err := VerifyDeviceRecord(tampered); err == nil || !strings.Contains(err.Error(), "self-signature") {
		t.Fatalf("expected missing signature to fail, got %v", err)
	}

	tampered = device
	tampered.SigningKey.Fingerprint = Fingerprint([]byte("wrong"))
	if err := VerifyDeviceRecord(tampered); err == nil || !strings.Contains(err.Error(), "signing fingerprint") {
		t.Fatalf("expected signing fingerprint mismatch to fail, got %v", err)
	}

	tampered = device
	tampered.EncryptionKey.Fingerprint = Fingerprint([]byte("wrong"))
	if err := VerifyDeviceRecord(tampered); err == nil || !strings.Contains(err.Error(), "encryption fingerprint") {
		t.Fatalf("expected encryption fingerprint mismatch to fail, got %v", err)
	}
}

func TestEnvelopeRoundTripAndTamperCases(t *testing.T) {
	senderIdentity, senderDevice := mustDeviceIdentity(t, "project-1", "Sender")
	recipientIdentity, recipientDevice := mustDeviceIdentity(t, "project-1", "Recipient")
	wrongRecipientIdentity, _ := mustDeviceIdentity(t, "project-1", "Wrong")

	meta := map[string]string{
		"project_id":      "project-1",
		"environment":     "production",
		"key_fingerprint": "fingerprint-1",
	}
	envelope, err := EncryptForDevice(senderIdentity, recipientDevice.EncryptionKey.PublicKey, []byte("deploy-secret"), meta)
	if err != nil {
		t.Fatal(err)
	}
	if !VerifyEnvelope(envelope, senderDevice.SigningKey.PublicKey) {
		t.Fatal("expected sender envelope signature to verify")
	}
	if VerifyEnvelope(envelope, recipientDevice.SigningKey.PublicKey) {
		t.Fatal("did not expect recipient key to verify sender signature")
	}
	plaintext, err := DecryptEnvelope(recipientIdentity, envelope)
	if err != nil {
		t.Fatal(err)
	}
	if string(plaintext) != "deploy-secret" {
		t.Fatalf("unexpected envelope plaintext %q", plaintext)
	}
	if _, err := DecryptEnvelope(wrongRecipientIdentity, envelope); err == nil {
		t.Fatal("expected wrong recipient identity to fail envelope decrypt")
	}

	tamperedSignature := cloneEnvelope(envelope)
	tamperedSignature.Meta["environment"] = "staging"
	if VerifyEnvelope(tamperedSignature, senderDevice.SigningKey.PublicKey) {
		t.Fatal("expected metadata tamper to invalidate envelope signature")
	}

	tamperedAAD := cloneEnvelope(envelope)
	tamperedAAD.AADB64 = B64([]byte(`{"project_id":"project-1","environment":"staging","key_fingerprint":"fingerprint-1"}`))
	if _, err := DecryptEnvelope(recipientIdentity, tamperedAAD); err == nil {
		t.Fatal("expected AAD tamper to fail envelope decrypt")
	}

	tamperedCiphertext := cloneEnvelope(envelope)
	ciphertext := mustB64(t, tamperedCiphertext.CiphertextB64)
	ciphertext[0] ^= 0xff
	tamperedCiphertext.CiphertextB64 = B64(ciphertext)
	if _, err := DecryptEnvelope(recipientIdentity, tamperedCiphertext); err == nil {
		t.Fatal("expected ciphertext tamper to fail envelope decrypt")
	}

	tamperedNonce := cloneEnvelope(envelope)
	nonce := mustB64(t, tamperedNonce.NonceB64)
	nonce[0] ^= 0xff
	tamperedNonce.NonceB64 = B64(nonce)
	if _, err := DecryptEnvelope(recipientIdentity, tamperedNonce); err == nil {
		t.Fatal("expected nonce tamper to fail envelope decrypt")
	}
}

func TestSecretRoundTripScopeAndTamperCases(t *testing.T) {
	identity, device := mustDeviceIdentity(t, "project-1", "Writer")
	envKeyRecord, _, envKey, err := NewEnvironmentKey("project-1", "production", identity, device)
	if err != nil {
		t.Fatal(err)
	}

	secret, err := BuildSecret(BuildSecretInput{
		ProjectID:            "project-1",
		Environment:          "production",
		Key:                  "APP_KEY",
		Plaintext:            "base64:secret",
		ChangeReason:         "test",
		EnvironmentKey:       envKey,
		EnvironmentKeyRecord: envKeyRecord,
		Identity:             identity,
		PreviousVersion:      2,
	})
	if err != nil {
		t.Fatal(err)
	}
	if !VerifySecretBody(secret, device.SigningKey.PublicKey, secret.ClientSig) {
		t.Fatal("expected secret body signature to verify")
	}
	plaintext, err := DecryptSecret(secret, envKey)
	if err != nil {
		t.Fatal(err)
	}
	if plaintext != "base64:secret" {
		t.Fatalf("unexpected secret plaintext %q", plaintext)
	}

	tamperedAAD := secret
	tamperedAAD.AAD.Env = "staging"
	if VerifySecretBody(tamperedAAD, device.SigningKey.PublicKey, secret.ClientSig) {
		t.Fatal("expected AAD tamper to invalidate secret signature")
	}
	if _, err := DecryptSecret(tamperedAAD, envKey); err == nil {
		t.Fatal("expected AAD tamper to fail secret decrypt")
	}

	tamperedCiphertext := secret
	ciphertext := mustB64(t, strings.TrimPrefix(secret.Ciphertext, "b64:"))
	ciphertext[0] ^= 0xff
	tamperedCiphertext.Ciphertext = "b64:" + B64(ciphertext)
	if VerifySecretBody(tamperedCiphertext, device.SigningKey.PublicKey, secret.ClientSig) {
		t.Fatal("expected ciphertext tamper to invalidate secret signature")
	}
	if _, err := DecryptSecret(tamperedCiphertext, envKey); err == nil {
		t.Fatal("expected ciphertext tamper to fail secret decrypt")
	}

	tamperedHMAC := secret
	tamperedHMAC.Claims = cloneStringMap(secret.Claims)
	tamperedHMAC.Claims["hmac"] = "b64:" + B64(bytes.Repeat([]byte{0}, 32))
	if _, err := DecryptSecret(tamperedHMAC, envKey); err == nil {
		t.Fatal("expected HMAC tamper to fail integrity check")
	}

	wrongEnvKey := append([]byte{}, envKey...)
	wrongEnvKey[0] ^= 0xff
	if _, err := DecryptSecret(secret, wrongEnvKey); err == nil {
		t.Fatal("expected wrong environment key to fail secret decrypt")
	}

	prodEnc, prodHMAC, err := DeriveValueKeys(envKey, "ghostable/project-1/production")
	if err != nil {
		t.Fatal(err)
	}
	stagingEnc, stagingHMAC, err := DeriveValueKeys(envKey, "ghostable/project-1/staging")
	if err != nil {
		t.Fatal(err)
	}
	if bytes.Equal(prodEnc, stagingEnc) || bytes.Equal(prodHMAC, stagingHMAC) {
		t.Fatal("expected environment scopes to derive separate keys")
	}
}

func TestEnvironmentKeyRotationProducesNewMaterial(t *testing.T) {
	identity, device := mustDeviceIdentity(t, "project-1", "Owner")
	previous, _, previousEnvKey, err := NewEnvironmentKey("project-1", "production", identity, device)
	if err != nil {
		t.Fatal(err)
	}
	secret, err := BuildSecret(BuildSecretInput{
		ProjectID:            "project-1",
		Environment:          "production",
		Key:                  "APP_KEY",
		Plaintext:            "old-secret",
		EnvironmentKey:       previousEnvKey,
		EnvironmentKeyRecord: previous,
		Identity:             identity,
	})
	if err != nil {
		t.Fatal(err)
	}

	rotated, rotatedEnvKey, rotatedDEK, err := RotateEnvironmentKey("project-1", "production", identity, previous)
	if err != nil {
		t.Fatal(err)
	}
	if rotated.Version != previous.Version+1 {
		t.Fatalf("expected rotated version %d, got %d", previous.Version+1, rotated.Version)
	}
	if rotated.Fingerprint == previous.Fingerprint || bytes.Equal(rotatedEnvKey, previousEnvKey) {
		t.Fatal("expected key rotation to create new environment key material")
	}
	decryptedRotatedKey, err := DecryptXChaCha(rotatedDEK, rotated.EncryptedKey, nil)
	if err != nil {
		t.Fatal(err)
	}
	if !bytes.Equal(decryptedRotatedKey, rotatedEnvKey) {
		t.Fatal("rotated encrypted key did not unwrap to rotated environment key")
	}
	if _, err := DecryptSecret(secret, rotatedEnvKey); err == nil {
		t.Fatal("expected old secret to reject rotated environment key")
	}
}

func TestEncodingUUIDAndCipherFailures(t *testing.T) {
	random, err := RandomBytes(16)
	if err != nil {
		t.Fatal(err)
	}
	if len(random) != 16 {
		t.Fatalf("expected 16 random bytes, got %d", len(random))
	}
	encoded := B64(random)
	decoded, err := UB64("b64:" + encoded)
	if err != nil {
		t.Fatal(err)
	}
	if !bytes.Equal(decoded, random) {
		t.Fatal("base64 helper did not round trip")
	}
	if _, err := UB64("not base64"); err == nil {
		t.Fatal("expected invalid base64 to fail")
	}
	if len(Fingerprint([]byte("value"))) != 64 {
		t.Fatal("expected SHA-256 fingerprint hex length")
	}
	uuid, err := UUID()
	if err != nil {
		t.Fatal(err)
	}
	if len(uuid) != 36 || uuid[14] != '4' {
		t.Fatalf("unexpected UUID: %s", uuid)
	}
	if _, err := DecryptXChaCha([]byte("short"), domain.EncryptedPayload{Alg: CipherAlg}, nil); err == nil {
		t.Fatal("expected short key to fail")
	}
	if _, err := DecryptXChaCha(bytes.Repeat([]byte{1}, 32), domain.EncryptedPayload{Alg: "wrong"}, nil); err == nil {
		t.Fatal("expected unsupported cipher to fail")
	}
}

func TestIdentityStoreFileSaveLoadDeleteAndValidation(t *testing.T) {
	root := t.TempDir()
	store := IdentityStore{fileRoot: root}
	identity := domain.LocalIdentityRecord{
		Schema:    domain.LocalIdentitySchema,
		ProjectID: "project-1",
		DeviceID:  "device-1",
	}
	if err := store.Save(identity); err != nil {
		t.Fatal(err)
	}
	loaded, err := store.Load("project-1")
	if err != nil {
		t.Fatal(err)
	}
	if loaded.ProjectID != "project-1" || loaded.DeviceID != "device-1" {
		t.Fatalf("unexpected loaded identity: %#v", loaded)
	}
	if store.Path("project/1") != filepath.Join(root, "project_1.json") {
		t.Fatalf("unexpected sanitized path: %s", store.Path("project/1"))
	}
	if err := store.Delete("project-1"); err != nil {
		t.Fatal(err)
	}
	if _, err := store.Load("project-1"); !os.IsNotExist(err) {
		t.Fatalf("expected deleted identity to be missing, got %v", err)
	}

	invalid := identity
	invalid.Schema = "wrong"
	writeIdentityFile(t, store, invalid)
	if _, err := store.Load("project-1"); err == nil || !strings.Contains(err.Error(), "unsupported identity schema") {
		t.Fatalf("expected invalid schema error, got %v", err)
	}

	invalid = identity
	invalid.ProjectID = "other-project"
	writeIdentityFile(t, store, invalid)
	if _, err := store.Load("project-1"); err == nil || !strings.Contains(err.Error(), "project id mismatch") {
		t.Fatalf("expected project mismatch error, got %v", err)
	}
}

func loadSecurityVectors(t *testing.T) securityVectors {
	t.Helper()
	path := filepath.Join("..", "..", "docs", "security", "test-vectors.json")
	file, err := os.Open(path)
	if err != nil {
		t.Fatal(err)
	}
	defer file.Close()

	var vectors securityVectors
	decoder := json.NewDecoder(file)
	decoder.UseNumber()
	if err := decoder.Decode(&vectors); err != nil {
		t.Fatal(err)
	}
	return vectors
}

func mustB64(t *testing.T, value string) []byte {
	t.Helper()
	decoded, err := base64.StdEncoding.DecodeString(value)
	if err != nil {
		t.Fatal(err)
	}
	return decoded
}

func mustDeviceIdentity(t *testing.T, projectID string, name string) (domain.LocalIdentityRecord, domain.DeviceRecord) {
	t.Helper()
	identity, device, err := NewDeviceIdentity(projectID, name, "test")
	if err != nil {
		t.Fatal(err)
	}
	return identity, device
}

func cloneEnvelope(envelope domain.EnvelopeJSON) domain.EnvelopeJSON {
	cloned := envelope
	if envelope.Meta != nil {
		cloned.Meta = cloneStringMap(envelope.Meta)
	}
	return cloned
}

func cloneStringMap(input map[string]string) map[string]string {
	cloned := make(map[string]string, len(input))
	for key, value := range input {
		cloned[key] = value
	}
	return cloned
}

func writeIdentityFile(t *testing.T, store IdentityStore, identity domain.LocalIdentityRecord) {
	t.Helper()
	content, err := json.Marshal(identity)
	if err != nil {
		t.Fatal(err)
	}
	if err := os.MkdirAll(store.fileRoot, 0o700); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(store.filePath("project-1"), content, 0o600); err != nil {
		t.Fatal(err)
	}
}

func TestFixtureSignatureIsStandardEd25519(t *testing.T) {
	vectors := loadSecurityVectors(t)
	publicKey := ed25519.PublicKey(mustB64(t, vectors.Ed25519CanonicalSignature.PublicKeyB64))
	signature := mustB64(t, vectors.Ed25519CanonicalSignature.SignatureB64)
	if !ed25519.Verify(publicKey, []byte(vectors.Ed25519CanonicalSignature.Canonical), signature) {
		t.Fatal("fixture signature did not verify with standard Ed25519")
	}
}
