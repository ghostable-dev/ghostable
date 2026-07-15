package crypto

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"os"
	"path/filepath"
	"runtime"
	"strings"
	"testing"
	"time"

	"github.com/ghostable-dev/ghostable/v3/internal/domain"
)

func TestAESGCMRoundTripAndTamperCases(t *testing.T) {
	key := bytes.Repeat([]byte{1}, keySizeBytes)
	aad := []byte("project\ndefault\nAPP_KEY\nvalue")
	payload, err := Encrypt(key, []byte("secret-value"), aad)
	if err != nil {
		t.Fatal(err)
	}
	if payload.Alg != AlgorithmAES256GCM {
		t.Fatalf("unexpected algorithm %q", payload.Alg)
	}
	if nonce := mustDecodeB64(t, payload.NonceB64); len(nonce) != nonceSizeBytes {
		t.Fatalf("expected nonce size %d, got %d", nonceSizeBytes, len(nonce))
	}

	plaintext, err := Decrypt(key, payload, aad)
	if err != nil {
		t.Fatal(err)
	}
	if string(plaintext) != "secret-value" {
		t.Fatalf("unexpected plaintext %q", plaintext)
	}

	wrongKey := bytes.Repeat([]byte{2}, keySizeBytes)
	if _, err := Decrypt(wrongKey, payload, aad); err == nil {
		t.Fatal("expected wrong key to fail")
	}
	if _, err := Decrypt(key, payload, []byte("wrong aad")); err == nil {
		t.Fatal("expected wrong AAD to fail")
	}

	tampered := payload
	ciphertext := mustDecodeB64(t, tampered.CiphertextB64)
	ciphertext[0] ^= 0xff
	tampered.CiphertextB64 = base64.StdEncoding.EncodeToString(ciphertext)
	if _, err := Decrypt(key, tampered, aad); err == nil {
		t.Fatal("expected ciphertext tamper to fail")
	}
}

func TestAESGCMRejectsInvalidInputs(t *testing.T) {
	if _, err := Encrypt([]byte("short"), []byte("secret"), nil); err == nil || !strings.Contains(err.Error(), "project key") {
		t.Fatalf("expected short encryption key error, got %v", err)
	}

	key := bytes.Repeat([]byte{1}, keySizeBytes)
	valid := domain.EncryptedPayload{
		Alg:           AlgorithmAES256GCM,
		NonceB64:      base64.StdEncoding.EncodeToString(bytes.Repeat([]byte{1}, nonceSizeBytes)),
		CiphertextB64: base64.StdEncoding.EncodeToString(bytes.Repeat([]byte{2}, 32)),
	}
	if _, err := Decrypt(key, domain.EncryptedPayload{Alg: "wrong"}, nil); err == nil || !strings.Contains(err.Error(), "unsupported") {
		t.Fatalf("expected unsupported algorithm error, got %v", err)
	}
	invalidNonce := valid
	invalidNonce.NonceB64 = "not base64"
	if _, err := Decrypt(key, invalidNonce, nil); err == nil || !strings.Contains(err.Error(), "nonce") {
		t.Fatalf("expected nonce decode error, got %v", err)
	}
	invalidCiphertext := valid
	invalidCiphertext.CiphertextB64 = "not base64"
	if _, err := Decrypt(key, invalidCiphertext, nil); err == nil || !strings.Contains(err.Error(), "ciphertext") {
		t.Fatalf("expected ciphertext decode error, got %v", err)
	}
	if _, err := Decrypt([]byte("short"), valid, nil); err == nil || !strings.Contains(err.Error(), "project key") {
		t.Fatalf("expected short decryption key error, got %v", err)
	}
}

func TestNewKeyReturnsUsableKey(t *testing.T) {
	key, err := NewKey()
	if err != nil {
		t.Fatal(err)
	}
	if len(key) != keySizeBytes {
		t.Fatalf("expected %d-byte key, got %d", keySizeBytes, len(key))
	}
	if _, err := Encrypt(key, []byte("secret"), nil); err != nil {
		t.Fatal(err)
	}
}

func TestKeyStoreLoadCreateDeleteAndPermissions(t *testing.T) {
	root := filepath.Join(t.TempDir(), "keys")
	t.Setenv("GHOSTABLE_KEYSTORE", root)
	store, err := NewKeyStore()
	if err != nil {
		t.Fatal(err)
	}
	if store.Path("project/1") != filepath.Join(root, "project_1.json") {
		t.Fatalf("unexpected sanitized path: %s", store.Path("project/1"))
	}

	record, key, created, err := store.LoadOrCreate("project/1", "device-1")
	if err != nil {
		t.Fatal(err)
	}
	if !created {
		t.Fatal("expected first LoadOrCreate to create a key")
	}
	if record.Schema != domain.LocalKeySchema || record.ProjectID != "project/1" || record.DeviceID != "device-1" {
		t.Fatalf("unexpected local key record: %#v", record)
	}
	if len(key) != keySizeBytes {
		t.Fatalf("expected %d-byte key, got %d", keySizeBytes, len(key))
	}

	loadedRecord, loadedKey, err := store.Load("project/1")
	if err != nil {
		t.Fatal(err)
	}
	if loadedRecord.ProjectID != record.ProjectID || !bytes.Equal(loadedKey, key) {
		t.Fatalf("loaded key mismatch: %#v", loadedRecord)
	}
	_, _, created, err = store.LoadOrCreate("project/1", "device-1")
	if err != nil {
		t.Fatal(err)
	}
	if created {
		t.Fatal("expected existing key to be loaded")
	}
	if runtime.GOOS != "windows" {
		assertMode(t, root, 0o700)
		assertMode(t, store.Path("project/1"), 0o600)
	}

	if err := store.Delete("project/1"); err != nil {
		t.Fatal(err)
	}
	if _, _, err := store.Load("project/1"); !os.IsNotExist(err) {
		t.Fatalf("expected deleted key to be missing, got %v", err)
	}
}

func TestKeyStoreRepairsLoosePermissions(t *testing.T) {
	if runtime.GOOS == "windows" {
		t.Skip("POSIX mode checks do not apply on Windows")
	}
	root := filepath.Join(t.TempDir(), "keys")
	if err := os.MkdirAll(root, 0o777); err != nil {
		t.Fatal(err)
	}
	if err := os.Chmod(root, 0o777); err != nil {
		t.Fatal(err)
	}
	store := KeyStore{root: root}
	if _, _, err := store.Create("project-1", "device-1"); err != nil {
		t.Fatal(err)
	}
	assertMode(t, root, 0o700)
	assertMode(t, store.Path("project-1"), 0o600)
}

func TestKeyStoreRejectsInvalidRecords(t *testing.T) {
	root := t.TempDir()
	store := KeyStore{root: root}
	writeLocalKeyRecord(t, store, "project-1", domain.LocalKeyRecord{
		Schema:    "wrong",
		ProjectID: "project-1",
		DeviceID:  "device-1",
		KeyB64:    base64.StdEncoding.EncodeToString(bytes.Repeat([]byte{1}, keySizeBytes)),
		CreatedAt: time.Now().UTC(),
	})
	if _, _, err := store.Load("project-1"); err == nil || !strings.Contains(err.Error(), "unsupported local key schema") {
		t.Fatalf("expected schema error, got %v", err)
	}

	writeLocalKeyRecord(t, store, "project-1", domain.LocalKeyRecord{
		Schema:    domain.LocalKeySchema,
		ProjectID: "other-project",
		DeviceID:  "device-1",
		KeyB64:    base64.StdEncoding.EncodeToString(bytes.Repeat([]byte{1}, keySizeBytes)),
		CreatedAt: time.Now().UTC(),
	})
	if _, _, err := store.Load("project-1"); err == nil || !strings.Contains(err.Error(), "project id mismatch") {
		t.Fatalf("expected project mismatch error, got %v", err)
	}

	writeLocalKeyRecord(t, store, "project-1", domain.LocalKeyRecord{
		Schema:    domain.LocalKeySchema,
		ProjectID: "project-1",
		DeviceID:  "device-1",
		KeyB64:    "not base64",
		CreatedAt: time.Now().UTC(),
	})
	if _, _, err := store.Load("project-1"); err == nil {
		t.Fatal("expected invalid key base64 to fail")
	}

	writeLocalKeyRecord(t, store, "project-1", domain.LocalKeyRecord{
		Schema:    domain.LocalKeySchema,
		ProjectID: "project-1",
		DeviceID:  "device-1",
		KeyB64:    base64.StdEncoding.EncodeToString([]byte("short")),
		CreatedAt: time.Now().UTC(),
	})
	if _, _, err := store.Load("project-1"); err == nil || !strings.Contains(err.Error(), "local key is not") {
		t.Fatalf("expected short key error, got %v", err)
	}
}

func mustDecodeB64(t *testing.T, value string) []byte {
	t.Helper()
	decoded, err := base64.StdEncoding.DecodeString(value)
	if err != nil {
		t.Fatal(err)
	}
	return decoded
}

func writeLocalKeyRecord(t *testing.T, store KeyStore, projectID string, record domain.LocalKeyRecord) {
	t.Helper()
	if err := os.MkdirAll(store.root, 0o700); err != nil {
		t.Fatal(err)
	}
	content, err := json.Marshal(record)
	if err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(store.Path(projectID), content, 0o600); err != nil {
		t.Fatal(err)
	}
}

func assertMode(t *testing.T, path string, expected os.FileMode) {
	t.Helper()
	info, err := os.Stat(path)
	if err != nil {
		t.Fatal(err)
	}
	if got := info.Mode().Perm(); got != expected {
		t.Fatalf("expected %s mode %o, got %o", path, expected, got)
	}
}
