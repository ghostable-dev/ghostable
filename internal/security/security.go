package security

import (
	"bytes"
	"crypto/ecdh"
	"crypto/ed25519"
	"crypto/hkdf"
	"crypto/hmac"
	"crypto/rand"
	"crypto/sha256"
	"encoding/base64"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"sort"
	"strconv"
	"strings"
	"time"

	"github.com/ghostable-dev/ghostable/v3/internal/domain"
	"golang.org/x/crypto/chacha20poly1305"
)

const (
	CipherAlg        = "xchacha20-poly1305"
	EnvelopeAlg      = "XChaCha20-Poly1305+HKDF-SHA256"
	envelopeHKDFInfo = "ghostable:v1:envelope"
	secretKeySize    = 32
	xChaChaNonceSize = chacha20poly1305.NonceSizeX
	envelopeVersion  = "v1"
)

func Now() string {
	return time.Now().UTC().Format(time.RFC3339Nano)
}

func RandomBytes(size int) ([]byte, error) {
	bytes := make([]byte, size)
	if _, err := io.ReadFull(rand.Reader, bytes); err != nil {
		return nil, err
	}
	return bytes, nil
}

func B64(bytes []byte) string {
	return base64.StdEncoding.EncodeToString(bytes)
}

func UB64(value string) ([]byte, error) {
	value = strings.TrimPrefix(value, "b64:")
	return base64.StdEncoding.DecodeString(value)
}

func Fingerprint(bytes []byte) string {
	sum := sha256.Sum256(bytes)
	return hex.EncodeToString(sum[:])
}

func DeviceIDForSigningPublicKey(publicKey []byte) string {
	return "dev_" + Fingerprint(publicKey)[:32]
}

func NewDeviceIdentity(projectID string, name string, platform string) (domain.LocalIdentityRecord, domain.DeviceRecord, error) {
	signingPublic, signingPrivate, err := ed25519.GenerateKey(rand.Reader)
	if err != nil {
		return domain.LocalIdentityRecord{}, domain.DeviceRecord{}, err
	}

	xKey, err := ecdh.X25519().GenerateKey(rand.Reader)
	if err != nil {
		return domain.LocalIdentityRecord{}, domain.DeviceRecord{}, err
	}
	encPrivate := xKey.Bytes()
	encPublic := xKey.PublicKey().Bytes()
	deviceID := DeviceIDForSigningPublicKey(signingPublic)
	now := Now()

	identity := domain.LocalIdentityRecord{
		Schema:                  domain.LocalIdentitySchema,
		ProjectID:               projectID,
		DeviceID:                deviceID,
		Name:                    name,
		Platform:                platform,
		CreatedAt:               now,
		SigningPublicKeyB64:     B64(signingPublic),
		SigningPrivateKeyB64:    B64(signingPrivate.Seed()),
		EncryptionPublicKeyB64:  B64(encPublic),
		EncryptionPrivateKeyB64: B64(encPrivate),
	}

	record := PublicDeviceRecord(identity)
	if err := SignDeviceRecord(&record, identity); err != nil {
		return domain.LocalIdentityRecord{}, domain.DeviceRecord{}, err
	}

	return identity, record, nil
}

func PublicDeviceRecord(identity domain.LocalIdentityRecord) domain.DeviceRecord {
	signingPublic, _ := UB64(identity.SigningPublicKeyB64)
	encryptionPublic, _ := UB64(identity.EncryptionPublicKeyB64)
	deviceID := DeviceIDForSigningPublicKey(signingPublic)
	return domain.DeviceRecord{
		Schema:    domain.DeviceSchema,
		ID:        deviceID,
		Name:      identity.Name,
		Platform:  identity.Platform,
		Status:    "active",
		CreatedAt: identity.CreatedAt,
		SigningKey: domain.PublicKeyRecord{
			Alg:         "Ed25519",
			PublicKey:   identity.SigningPublicKeyB64,
			Fingerprint: Fingerprint(signingPublic),
		},
		EncryptionKey: domain.PublicKeyRecord{
			Alg:         "X25519",
			PublicKey:   identity.EncryptionPublicKeyB64,
			Fingerprint: Fingerprint(encryptionPublic),
		},
		DeviceID: deviceID,
	}
}

func SignDeviceRecord(record *domain.DeviceRecord, identity domain.LocalIdentityRecord) error {
	record.DeviceID = record.ID
	record.ClientSig = ""
	signature, err := SignCanonical(*record, identity)
	if err != nil {
		return err
	}
	record.ClientSig = signature
	return nil
}

func VerifyDeviceRecord(record domain.DeviceRecord) error {
	if record.Schema != domain.DeviceSchema {
		return fmt.Errorf("device %s has an invalid schema", record.ID)
	}
	if record.Status != "active" {
		return fmt.Errorf("device %s is not active", record.ID)
	}
	publicKey, err := UB64(record.SigningKey.PublicKey)
	if err != nil {
		return err
	}
	expectedID := DeviceIDForSigningPublicKey(publicKey)
	if record.ID != expectedID {
		return fmt.Errorf("device %s is not bound to its signing key", record.ID)
	}
	if record.DeviceID != record.ID || record.ClientSig == "" {
		return fmt.Errorf("device %s is missing a valid self-signature", record.ID)
	}
	if record.SigningKey.Fingerprint != Fingerprint(publicKey) {
		return fmt.Errorf("device %s signing fingerprint does not match", record.ID)
	}
	encryptionPublic, err := UB64(record.EncryptionKey.PublicKey)
	if err != nil {
		return err
	}
	if record.EncryptionKey.Fingerprint != Fingerprint(encryptionPublic) {
		return fmt.Errorf("device %s encryption fingerprint does not match", record.ID)
	}
	signature := record.ClientSig
	record.ClientSig = ""
	if !VerifyCanonical(record, record.SigningKey.PublicKey, signature) {
		return fmt.Errorf("device %s self-signature could not be verified", record.ID)
	}
	return nil
}

func SignCanonical(value interface{}, identity domain.LocalIdentityRecord) (string, error) {
	payload, err := withDeviceID(value, identity.DeviceID)
	if err != nil {
		return "", err
	}
	canonical, err := CanonicalJSON(payload)
	if err != nil {
		return "", err
	}
	privateKey, err := signingPrivateKey(identity)
	if err != nil {
		return "", err
	}
	return B64(ed25519.Sign(privateKey, []byte(canonical))), nil
}

func VerifyCanonical(value interface{}, publicKeyB64 string, signatureB64 string) bool {
	payload, err := toJSONMap(value)
	if err != nil {
		return false
	}
	delete(payload, "client_sig")
	canonical, err := CanonicalJSON(payload)
	if err != nil {
		return false
	}
	publicKey, err := UB64(publicKeyB64)
	if err != nil {
		return false
	}
	signature, err := UB64(signatureB64)
	if err != nil {
		return false
	}
	return ed25519.Verify(ed25519.PublicKey(publicKey), []byte(canonical), signature)
}

func SignSecretBody(secret *domain.SecretBody, identity domain.LocalIdentityRecord) error {
	secret.ClientSig = ""
	body, err := secretSignatureBody(*secret)
	if err != nil {
		return err
	}
	encoded, err := json.Marshal(body)
	if err != nil {
		return err
	}
	privateKey, err := signingPrivateKey(identity)
	if err != nil {
		return err
	}
	secret.ClientSig = B64(ed25519.Sign(privateKey, encoded))
	return nil
}

func VerifySecretBody(secret domain.SecretBody, publicKeyB64 string, signatureB64 string) bool {
	body, err := secretSignatureBody(secret)
	if err != nil {
		return false
	}
	encoded, err := json.Marshal(body)
	if err != nil {
		return false
	}
	publicKey, err := UB64(publicKeyB64)
	if err != nil {
		return false
	}
	signature, err := UB64(signatureB64)
	if err != nil {
		return false
	}
	return ed25519.Verify(ed25519.PublicKey(publicKey), encoded, signature)
}

func NewEnvironmentKey(projectID string, envName string, identity domain.LocalIdentityRecord, device domain.DeviceRecord) (domain.EnvironmentKeyRecord, domain.AccessGrantRecord, []byte, error) {
	envKey, err := RandomBytes(secretKeySize)
	if err != nil {
		return domain.EnvironmentKeyRecord{}, domain.AccessGrantRecord{}, nil, err
	}
	dek, err := RandomBytes(secretKeySize)
	if err != nil {
		return domain.EnvironmentKeyRecord{}, domain.AccessGrantRecord{}, nil, err
	}
	encryptedKey, err := EncryptXChaCha(dek, envKey, nil)
	if err != nil {
		return domain.EnvironmentKeyRecord{}, domain.AccessGrantRecord{}, nil, err
	}
	now := Now()
	record := domain.EnvironmentKeyRecord{
		Schema:            domain.EnvironmentKeySchema,
		ProjectID:         projectID,
		Environment:       envName,
		Version:           1,
		Fingerprint:       Fingerprint(envKey),
		EncryptedKey:      encryptedKey,
		CreatedByDeviceID: identity.DeviceID,
		CreatedAt:         now,
		UpdatedAt:         now,
	}
	grant, err := NewAccessGrant(projectID, envName, identity, device, dek, record)
	if err != nil {
		return domain.EnvironmentKeyRecord{}, domain.AccessGrantRecord{}, nil, err
	}
	return record, grant, envKey, nil
}

func RotateEnvironmentKey(projectID string, envName string, identity domain.LocalIdentityRecord, previous domain.EnvironmentKeyRecord) (domain.EnvironmentKeyRecord, []byte, []byte, error) {
	envKey, err := RandomBytes(secretKeySize)
	if err != nil {
		return domain.EnvironmentKeyRecord{}, nil, nil, err
	}
	dek, err := RandomBytes(secretKeySize)
	if err != nil {
		return domain.EnvironmentKeyRecord{}, nil, nil, err
	}
	encryptedKey, err := EncryptXChaCha(dek, envKey, nil)
	if err != nil {
		return domain.EnvironmentKeyRecord{}, nil, nil, err
	}
	now := Now()
	version := previous.Version + 1
	if version < 1 {
		version = 1
	}
	record := domain.EnvironmentKeyRecord{
		Schema:            domain.EnvironmentKeySchema,
		ProjectID:         projectID,
		Environment:       envName,
		Version:           version,
		Fingerprint:       Fingerprint(envKey),
		EncryptedKey:      encryptedKey,
		CreatedByDeviceID: identity.DeviceID,
		CreatedAt:         now,
		UpdatedAt:         now,
	}
	return record, envKey, dek, nil
}

func NewAccessGrant(projectID string, envName string, identity domain.LocalIdentityRecord, device domain.DeviceRecord, dek []byte, envKey domain.EnvironmentKeyRecord) (domain.AccessGrantRecord, error) {
	envelope, err := EncryptForDevice(identity, device.EncryptionKey.PublicKey, dek, map[string]string{
		"project_id":      projectID,
		"environment":     envName,
		"key_fingerprint": envKey.Fingerprint,
	})
	if err != nil {
		return domain.AccessGrantRecord{}, err
	}
	grant := domain.AccessGrantRecord{
		Schema:            domain.AccessGrantSchema,
		ProjectID:         projectID,
		Environment:       envName,
		DeviceID:          device.ID,
		GrantedByDeviceID: identity.DeviceID,
		GrantedAt:         Now(),
		EnvKeyVersion:     envKey.Version,
		EnvKeyFingerprint: envKey.Fingerprint,
		Envelope:          envelope,
		SignerDeviceID:    identity.DeviceID,
	}
	signature, err := SignCanonical(grant, identity)
	if err != nil {
		return domain.AccessGrantRecord{}, err
	}
	grant.ClientSig = signature
	return grant, nil
}

func EncryptForDevice(identity domain.LocalIdentityRecord, recipientPublicKeyB64 string, plaintext []byte, meta map[string]string) (domain.EnvelopeJSON, error) {
	recipientPublic, err := UB64(recipientPublicKeyB64)
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	recipient, err := ecdh.X25519().NewPublicKey(recipientPublic)
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	ephemeral, err := ecdh.X25519().GenerateKey(rand.Reader)
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	shared, err := ephemeral.ECDH(recipient)
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	key, err := hkdf.Key(sha256.New, shared, nil, envelopeHKDFInfo, secretKeySize)
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	aad := metaAAD(meta)
	encrypted, err := EncryptXChaCha(key, plaintext, aad)
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	signingPublic, _ := UB64(identity.SigningPublicKeyB64)
	envelopeID, err := UUID()
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	envelope := domain.EnvelopeJSON{
		ID:                     envelopeID,
		Version:                envelopeVersion,
		Alg:                    EnvelopeAlg,
		ToDevicePublicKey:      recipientPublicKeyB64,
		FromEphemeralPublicKey: B64(ephemeral.PublicKey().Bytes()),
		NonceB64:               encrypted.NonceB64,
		CiphertextB64:          encrypted.CiphertextB64,
		CreatedAt:              Now(),
		Meta:                   meta,
		SenderKID:              Fingerprint(signingPublic),
	}
	if len(aad) > 0 {
		envelope.AADB64 = B64(aad)
	}
	signature, err := SignEnvelope(envelope, identity)
	if err != nil {
		return domain.EnvelopeJSON{}, err
	}
	envelope.SignatureB64 = signature
	return envelope, nil
}

func DecryptEnvelope(identity domain.LocalIdentityRecord, envelope domain.EnvelopeJSON) ([]byte, error) {
	privateBytes, err := UB64(identity.EncryptionPrivateKeyB64)
	if err != nil {
		return nil, err
	}
	privateKey, err := ecdh.X25519().NewPrivateKey(privateBytes)
	if err != nil {
		return nil, err
	}
	ephemeralBytes, err := UB64(envelope.FromEphemeralPublicKey)
	if err != nil {
		return nil, err
	}
	ephemeral, err := ecdh.X25519().NewPublicKey(ephemeralBytes)
	if err != nil {
		return nil, err
	}
	shared, err := privateKey.ECDH(ephemeral)
	if err != nil {
		return nil, err
	}
	key, err := hkdf.Key(sha256.New, shared, nil, envelopeHKDFInfo, secretKeySize)
	if err != nil {
		return nil, err
	}
	aad := metaAAD(envelope.Meta)
	if envelope.AADB64 != "" {
		aad, err = UB64(envelope.AADB64)
		if err != nil {
			return nil, err
		}
	}
	return DecryptXChaCha(key, domain.EncryptedPayload{
		Alg:           CipherAlg,
		NonceB64:      envelope.NonceB64,
		CiphertextB64: envelope.CiphertextB64,
	}, aad)
}

func SignEnvelope(envelope domain.EnvelopeJSON, identity domain.LocalIdentityRecord) (string, error) {
	privateKey, err := signingPrivateKey(identity)
	if err != nil {
		return "", err
	}
	return B64(ed25519.Sign(privateKey, []byte(envelopeSignaturePayload(envelope)))), nil
}

func VerifyEnvelope(envelope domain.EnvelopeJSON, senderPublicKeyB64 string) bool {
	publicKey, err := UB64(senderPublicKeyB64)
	if err != nil {
		return false
	}
	signature, err := UB64(envelope.SignatureB64)
	if err != nil {
		return false
	}
	return ed25519.Verify(ed25519.PublicKey(publicKey), []byte(envelopeSignaturePayload(envelope)), signature)
}

func envelopeSignaturePayload(envelope domain.EnvelopeJSON) string {
	return strings.Join([]string{
		envelope.ID,
		envelope.Version,
		envelope.ToDevicePublicKey,
		envelope.FromEphemeralPublicKey,
		envelope.NonceB64,
		envelope.CiphertextB64,
		envelope.CreatedAt,
		metaForSignature(envelope.Meta),
	}, ":")
}

func EncryptXChaCha(key []byte, plaintext []byte, aad []byte) (domain.EncryptedPayload, error) {
	aead, err := chacha20poly1305.NewX(key)
	if err != nil {
		return domain.EncryptedPayload{}, err
	}
	nonce, err := RandomBytes(xChaChaNonceSize)
	if err != nil {
		return domain.EncryptedPayload{}, err
	}
	ciphertext := aead.Seal(nil, nonce, plaintext, aad)
	return domain.EncryptedPayload{
		Alg:           CipherAlg,
		NonceB64:      B64(nonce),
		CiphertextB64: B64(ciphertext),
	}, nil
}

func DecryptXChaCha(key []byte, payload domain.EncryptedPayload, aad []byte) ([]byte, error) {
	if payload.Alg != CipherAlg && payload.Alg != "" {
		return nil, fmt.Errorf("unsupported cipher %q", payload.Alg)
	}
	nonce, err := UB64(payload.NonceB64)
	if err != nil {
		return nil, err
	}
	ciphertext, err := UB64(payload.CiphertextB64)
	if err != nil {
		return nil, err
	}
	aead, err := chacha20poly1305.NewX(key)
	if err != nil {
		return nil, err
	}
	return aead.Open(nil, nonce, ciphertext, aad)
}

type BuildSecretInput struct {
	ProjectID            string
	Environment          string
	Key                  string
	Plaintext            string
	ChangeReason         string
	EnvironmentKey       []byte
	EnvironmentKeyRecord domain.EnvironmentKeyRecord
	Identity             domain.LocalIdentityRecord
	PreviousVersion      int
}

func BuildSecret(input BuildSecretInput) (domain.SecretBody, error) {
	encKey, hmacKey, err := DeriveValueKeys(input.EnvironmentKey, domain.GhostableOrgScope+"/"+input.ProjectID+"/"+input.Environment)
	if err != nil {
		return domain.SecretBody{}, err
	}
	aad := domain.SecretAAD{Org: domain.GhostableOrgScope, Project: input.ProjectID, Env: input.Environment, Name: input.Key}
	encrypted, err := EncryptXChaCha(encKey, []byte(input.Plaintext), aadBytes(aad))
	if err != nil {
		return domain.SecretBody{}, err
	}
	mac := hmac.New(sha256.New, hmacKey)
	mac.Write([]byte(input.Plaintext))
	secret := domain.SecretBody{
		Name:              input.Key,
		Env:               input.Environment,
		Ciphertext:        "b64:" + encrypted.CiphertextB64,
		Nonce:             "b64:" + encrypted.NonceB64,
		Alg:               CipherAlg,
		AAD:               aad,
		Claims:            map[string]string{"hmac": "b64:" + B64(mac.Sum(nil))},
		EnvKekVersion:     input.EnvironmentKeyRecord.Version,
		EnvKekFingerprint: input.EnvironmentKeyRecord.Fingerprint,
		LineBytes:         len([]byte(input.Plaintext)),
	}
	if input.PreviousVersion > 0 {
		secret.IfVersion = &input.PreviousVersion
	}
	if reason := strings.TrimSpace(input.ChangeReason); reason != "" {
		secret.Change = &domain.ValueChangeContext{Reason: reason}
	}
	if err := SignSecretBody(&secret, input.Identity); err != nil {
		return domain.SecretBody{}, err
	}
	return secret, nil
}

func DecryptSecret(secret domain.SecretBody, envKey []byte) (string, error) {
	encKey, hmacKey, err := DeriveValueKeys(envKey, secret.AAD.Org+"/"+secret.AAD.Project+"/"+secret.AAD.Env)
	if err != nil {
		return "", err
	}
	nonce, err := UB64(secret.Nonce)
	if err != nil {
		return "", err
	}
	ciphertext, err := UB64(secret.Ciphertext)
	if err != nil {
		return "", err
	}
	plaintext, err := DecryptXChaCha(encKey, domain.EncryptedPayload{
		Alg:           secret.Alg,
		NonceB64:      B64(nonce),
		CiphertextB64: B64(ciphertext),
	}, aadBytes(secret.AAD))
	if err != nil {
		return "", err
	}
	if expected := secret.Claims["hmac"]; expected != "" {
		mac := hmac.New(sha256.New, hmacKey)
		mac.Write(plaintext)
		if "b64:"+B64(mac.Sum(nil)) != expected {
			return "", fmt.Errorf("integrity check failed for %s", secret.Name)
		}
	}
	return string(plaintext), nil
}

func DeriveValueKeys(master []byte, scope string) ([]byte, []byte, error) {
	okm, err := hkdf.Key(sha256.New, master, []byte("ghostable:"+scope), "", 64)
	if err != nil {
		return nil, nil, err
	}
	return okm[:32], okm[32:], nil
}

func CanonicalJSON(value interface{}) (string, error) {
	normalized, err := normalizeJSON(value)
	if err != nil {
		return "", err
	}
	var builder strings.Builder
	writeCanonical(&builder, normalized)
	return builder.String(), nil
}

func UUID() (string, error) {
	bytes, err := RandomBytes(16)
	if err != nil {
		return "", err
	}
	bytes[6] = (bytes[6] & 0x0f) | 0x40
	bytes[8] = (bytes[8] & 0x3f) | 0x80
	return fmt.Sprintf("%x-%x-%x-%x-%x", bytes[0:4], bytes[4:6], bytes[6:8], bytes[8:10], bytes[10:16]), nil
}

func aadBytes(aad domain.SecretAAD) []byte {
	return []byte(fmt.Sprintf(`{"org":%s,"project":%s,"env":%s,"name":%s}`,
		strconv.Quote(aad.Org),
		strconv.Quote(aad.Project),
		strconv.Quote(aad.Env),
		strconv.Quote(aad.Name),
	))
}

func signingPrivateKey(identity domain.LocalIdentityRecord) (ed25519.PrivateKey, error) {
	seed, err := UB64(identity.SigningPrivateKeyB64)
	if err != nil {
		return nil, err
	}
	if len(seed) != ed25519.SeedSize {
		return nil, fmt.Errorf("invalid Ed25519 private key seed")
	}
	return ed25519.NewKeyFromSeed(seed), nil
}

func withDeviceID(value interface{}, deviceID string) (map[string]interface{}, error) {
	payload, err := toJSONMap(value)
	if err != nil {
		return nil, err
	}
	payload["device_id"] = deviceID
	delete(payload, "client_sig")
	return payload, nil
}

func toJSONMap(value interface{}) (map[string]interface{}, error) {
	encoded, err := json.Marshal(value)
	if err != nil {
		return nil, err
	}
	decoder := json.NewDecoder(bytes.NewReader(encoded))
	decoder.UseNumber()
	var decoded map[string]interface{}
	if err := decoder.Decode(&decoded); err != nil {
		return nil, err
	}
	return decoded, nil
}

func normalizeJSON(value interface{}) (interface{}, error) {
	encoded, err := json.Marshal(value)
	if err != nil {
		return nil, err
	}
	decoder := json.NewDecoder(bytes.NewReader(encoded))
	decoder.UseNumber()
	var decoded interface{}
	if err := decoder.Decode(&decoded); err != nil {
		return nil, err
	}
	return decoded, nil
}

func writeCanonical(builder *strings.Builder, value interface{}) {
	switch typed := value.(type) {
	case nil:
		builder.WriteString("null")
	case bool:
		if typed {
			builder.WriteString("true")
		} else {
			builder.WriteString("false")
		}
	case string:
		builder.WriteString(strconv.Quote(typed))
	case json.Number:
		builder.WriteString(typed.String())
	case float64:
		builder.WriteString(strconv.FormatFloat(typed, 'f', -1, 64))
	case []interface{}:
		builder.WriteByte('[')
		for index, item := range typed {
			if index > 0 {
				builder.WriteByte(',')
			}
			writeCanonical(builder, item)
		}
		builder.WriteByte(']')
	case map[string]interface{}:
		keys := make([]string, 0, len(typed))
		for key, value := range typed {
			if value != nil {
				keys = append(keys, key)
			}
		}
		sort.Strings(keys)
		builder.WriteByte('{')
		for index, key := range keys {
			if index > 0 {
				builder.WriteByte(',')
			}
			builder.WriteString(strconv.Quote(key))
			builder.WriteByte(':')
			writeCanonical(builder, typed[key])
		}
		builder.WriteByte('}')
	default:
		encoded, _ := json.Marshal(typed)
		builder.Write(encoded)
	}
}

func metaAAD(meta map[string]string) []byte {
	if len(meta) == 0 {
		return nil
	}
	return []byte(metaForSignature(meta))
}

func metaForSignature(meta map[string]string) string {
	if len(meta) == 0 {
		return ""
	}
	keys := make([]string, 0, len(meta))
	preferred := []string{"project_id", "environment", "key_fingerprint"}
	for _, key := range preferred {
		if _, ok := meta[key]; ok {
			keys = append(keys, key)
		}
	}
	remaining := make([]string, 0, len(meta))
	for key := range meta {
		if key != "project_id" && key != "environment" && key != "key_fingerprint" {
			remaining = append(remaining, key)
		}
	}
	sort.Strings(remaining)
	keys = append(keys, remaining...)

	var builder strings.Builder
	builder.WriteByte('{')
	for index, key := range keys {
		if index > 0 {
			builder.WriteByte(',')
		}
		builder.WriteString(strconv.Quote(key))
		builder.WriteByte(':')
		builder.WriteString(strconv.Quote(meta[key]))
	}
	builder.WriteByte('}')
	return builder.String()
}

func secretSignatureBody(secret domain.SecretBody) (map[string]interface{}, error) {
	encoded, err := json.Marshal(secret)
	if err != nil {
		return nil, err
	}
	decoder := json.NewDecoder(bytes.NewReader(encoded))
	decoder.UseNumber()
	var body map[string]interface{}
	if err := decoder.Decode(&body); err != nil {
		return nil, err
	}
	delete(body, "client_sig")
	return body, nil
}
