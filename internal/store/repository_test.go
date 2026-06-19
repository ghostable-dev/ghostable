package store

import (
	"encoding/json"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/beta/internal/domain"
	"github.com/ghostable-dev/beta/internal/dotenv"
)

func TestRepositoryEncryptsAndPullsVariables(t *testing.T) {
	root := t.TempDir()
	t.Setenv("GHOSTABLE_KEYSTORE", filepath.Join(root, "keys"))

	repo, _, err := Setup(root, SetupOptions{
		Name:         "Test Project",
		Environments: []domain.Environment{{Name: "local", Type: "local"}},
		DeviceName:   "test-device",
	})
	if err != nil {
		t.Fatal(err)
	}

	_, err = repo.PutVariables("local", map[string]string{
		"APP_KEY": "super-secret-value",
	}, PutOptions{Reason: "test"})
	if err != nil {
		t.Fatal(err)
	}

	valueFiles, err := filepath.Glob(filepath.Join(root, ".ghostable", "environments", "local", "values", "*.json"))
	if err != nil {
		t.Fatal(err)
	}
	if len(valueFiles) != 1 {
		t.Fatalf("expected one encrypted value file, got %d", len(valueFiles))
	}
	content, err := os.ReadFile(valueFiles[0])
	if err != nil {
		t.Fatal(err)
	}
	if strings.Contains(string(content), "super-secret-value") {
		t.Fatal("plaintext secret leaked into encrypted value file")
	}
	var record domain.ValueRecord
	if err := json.Unmarshal(content, &record); err != nil {
		t.Fatal(err)
	}
	if record.Schema != domain.ValueSchema {
		t.Fatalf("expected TS-compatible value schema, got %s", record.Schema)
	}
	if record.Secret.ClientSig == "" {
		t.Fatal("expected signed value payload")
	}
	if record.Secret.Alg != "xchacha20-poly1305" {
		t.Fatalf("expected xchacha20-poly1305, got %s", record.Secret.Alg)
	}

	grantFiles, err := filepath.Glob(filepath.Join(root, ".ghostable", "environments", "local", "access", "*.json"))
	if err != nil {
		t.Fatal(err)
	}
	if len(grantFiles) != 1 {
		t.Fatalf("expected one access grant, got %d", len(grantFiles))
	}
	var grant domain.AccessGrantRecord
	grantContent, err := os.ReadFile(grantFiles[0])
	if err != nil {
		t.Fatal(err)
	}
	if err := json.Unmarshal(grantContent, &grant); err != nil {
		t.Fatal(err)
	}
	if grant.ClientSig == "" || grant.Envelope.SignatureB64 == "" {
		t.Fatal("expected signed grant and signed envelope")
	}

	variables, err := repo.ReadVariables("local")
	if err != nil {
		t.Fatal(err)
	}
	if variables["APP_KEY"].Value != "super-secret-value" {
		t.Fatalf("unexpected decrypted value: %q", variables["APP_KEY"].Value)
	}

	result, _, err := repo.Pull("local", PullOptions{File: ".env", Backup: true})
	if err != nil {
		t.Fatal(err)
	}
	if result.Written != 1 {
		t.Fatalf("expected one written variable, got %d", result.Written)
	}

	envFile, err := os.Open(filepath.Join(root, ".env"))
	if err != nil {
		t.Fatal(err)
	}
	defer envFile.Close()

	parsed, err := dotenv.Parse(envFile)
	if err != nil {
		t.Fatal(err)
	}
	if parsed.Entries["APP_KEY"].Value != "super-secret-value" {
		t.Fatalf("unexpected pulled value: %q", parsed.Entries["APP_KEY"].Value)
	}
}

func TestRepositoryRejectsTamperedValueSignature(t *testing.T) {
	root := t.TempDir()
	t.Setenv("GHOSTABLE_KEYSTORE", filepath.Join(root, "keys"))

	repo, _, err := Setup(root, SetupOptions{
		Name:         "Test Project",
		Environments: []domain.Environment{{Name: "local", Type: "local"}},
		DeviceName:   "test-device",
	})
	if err != nil {
		t.Fatal(err)
	}
	if _, err := repo.PutVariables("local", map[string]string{"APP_KEY": "super-secret-value"}, PutOptions{Reason: "test"}); err != nil {
		t.Fatal(err)
	}

	valueFiles, err := filepath.Glob(filepath.Join(root, ".ghostable", "environments", "local", "values", "*.json"))
	if err != nil {
		t.Fatal(err)
	}
	content, err := os.ReadFile(valueFiles[0])
	if err != nil {
		t.Fatal(err)
	}
	var record domain.ValueRecord
	if err := json.Unmarshal(content, &record); err != nil {
		t.Fatal(err)
	}
	record.Secret.Name = "OTHER_KEY"
	tampered, err := json.MarshalIndent(record, "", "  ")
	if err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(valueFiles[0], append(tampered, '\n'), 0o600); err != nil {
		t.Fatal(err)
	}

	if _, err := repo.ReadVariables("local"); err == nil {
		t.Fatal("expected tampered value signature to be rejected")
	}
}

func TestReaderDeviceCannotWriteVariables(t *testing.T) {
	root := t.TempDir()
	t.Setenv("GHOSTABLE_KEYSTORE", filepath.Join(root, "keys"))

	ownerRepo, _, err := Setup(root, SetupOptions{
		Name:         "Test Project",
		Environments: []domain.Environment{{Name: "local", Type: "local"}},
		DeviceName:   "owner-device",
	})
	if err != nil {
		t.Fatal(err)
	}
	if _, err := ownerRepo.PutVariables("local", map[string]string{"APP_KEY": "owner-value"}, PutOptions{Reason: "owner"}); err != nil {
		t.Fatal(err)
	}

	readerDevice, _, err := ownerRepo.JoinDevice("reader-device", "test")
	if err != nil {
		t.Fatal(err)
	}
	if err := ownerRepo.ShareDevice(readerDevice.ID, "local", "reader"); err != nil {
		t.Fatal(err)
	}

	readerRepo, err := Open(root)
	if err != nil {
		t.Fatal(err)
	}
	values, err := readerRepo.ReadVariables("local")
	if err != nil {
		t.Fatal(err)
	}
	if values["APP_KEY"].Value != "owner-value" {
		t.Fatalf("reader should decrypt shared value, got %q", values["APP_KEY"].Value)
	}

	if _, err := readerRepo.PutVariables("local", map[string]string{"APP_KEY": "reader-value"}, PutOptions{Reason: "reader"}); err == nil {
		t.Fatal("expected reader device write to be rejected")
	}
}

func TestRepositoryDeviceGrantsAndRevoke(t *testing.T) {
	root := t.TempDir()
	t.Setenv("GHOSTABLE_KEYSTORE", filepath.Join(root, "keys"))

	repo, _, err := Setup(root, SetupOptions{
		Name:         "Test Project",
		Environments: []domain.Environment{{Name: "local", Type: "local"}},
		DeviceName:   "owner-device",
	})
	if err != nil {
		t.Fatal(err)
	}

	created, err := repo.CreateAutomationCredential("ci-device", "ci", []AutomationCredentialGrant{{EnvironmentName: "local", Role: "writer"}})
	if err != nil {
		t.Fatal(err)
	}

	grants, err := repo.DeviceGrants("local")
	if err != nil {
		t.Fatal(err)
	}
	if roleForGrant(grants, created.Credential.DeviceID, "local") != "writer" {
		t.Fatalf("expected writer grant for credential, got %#v", grants)
	}

	grantFiles, err := filepath.Glob(filepath.Join(root, ".ghostable", "environments", "local", "access", "*.json"))
	if err != nil {
		t.Fatal(err)
	}
	if len(grantFiles) != 2 {
		t.Fatalf("expected owner and credential access grants, got %d", len(grantFiles))
	}
	if _, err := repo.DeleteDevice(created.Credential.DeviceID); err == nil {
		t.Fatal("expected active credential device delete to be rejected")
	}

	result, err := repo.RevokeDevice(created.Credential.DeviceID, "local")
	if err != nil {
		t.Fatal(err)
	}
	if !result.Revoked || len(result.Removed) != 1 {
		t.Fatalf("expected one revoked grant, got %#v", result)
	}

	grants, err = repo.DeviceGrants("local")
	if err != nil {
		t.Fatal(err)
	}
	if roleForGrant(grants, created.Credential.DeviceID, "local") != "" {
		t.Fatalf("expected credential grant to be removed, got %#v", grants)
	}

	grantFiles, err = filepath.Glob(filepath.Join(root, ".ghostable", "environments", "local", "access", "*.json"))
	if err != nil {
		t.Fatal(err)
	}
	if len(grantFiles) != 1 {
		t.Fatalf("expected only owner access grant after revoke, got %d", len(grantFiles))
	}

	deleteResult, err := repo.DeleteDevice(created.Credential.DeviceID)
	if err != nil {
		t.Fatal(err)
	}
	if !deleteResult.Deleted || deleteResult.DeviceID != created.Credential.DeviceID {
		t.Fatalf("unexpected delete result: %#v", deleteResult)
	}
	devices, err := repo.Devices()
	if err != nil {
		t.Fatal(err)
	}
	for _, device := range devices {
		if device.ID == created.Credential.DeviceID {
			t.Fatalf("expected credential device record to be deleted, got %#v", devices)
		}
	}
	if _, err := os.Stat(filepath.Join(root, ".ghostable", "devices", idFileName(created.Credential.DeviceID))); !os.IsNotExist(err) {
		t.Fatalf("expected credential device file to be deleted, stat err: %v", err)
	}
}

func roleForGrant(grants []DeviceGrant, deviceID string, env string) string {
	for _, grant := range grants {
		if grant.DeviceID == deviceID && grant.Environment == env {
			return grant.Role
		}
	}
	return ""
}
