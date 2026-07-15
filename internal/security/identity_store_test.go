package security

import (
	"path/filepath"
	"runtime"
	"strings"
	"testing"

	"github.com/ghostable-dev/ghostable/v3/internal/domain"
)

func TestIdentityStorePathUsesKeystoreOverride(t *testing.T) {
	root := t.TempDir()
	t.Setenv("GHOSTABLE_KEYSTORE", root)

	store, err := NewIdentityStore()
	if err != nil {
		t.Fatal(err)
	}

	expected := filepath.Join(root, "project_1.json")
	if path := store.Path("project/1"); path != expected {
		t.Fatalf("expected override path %s, got %s", expected, path)
	}
}

func TestMacOSSecurityPathIgnoresPATH(t *testing.T) {
	t.Setenv("PATH", t.TempDir())

	if path := macOSSecurityPath(); path != macOSSecurityExecutable {
		t.Fatalf("expected trusted security path %s, got %s", macOSSecurityExecutable, path)
	}
}

func TestNewIdentityStoreUsesFileBackedStoreOnMacOS(t *testing.T) {
	if runtime.GOOS != "darwin" {
		t.Skip("macOS-only identity store behavior")
	}
	t.Setenv("GHOSTABLE_KEYSTORE", "")

	store, err := NewIdentityStore()
	if err != nil {
		t.Fatal(err)
	}
	if store.usesKeychain() {
		t.Fatal("expected new macOS identity store to avoid Keychain argv writes")
	}
	if strings.Contains(store.Path("project-1"), "Keychain") {
		t.Fatalf("expected file-backed identity path, got %s", store.Path("project-1"))
	}
	if !store.keychainFallback {
		t.Fatal("expected macOS identity store to retain read fallback for existing Keychain entries")
	}
}

func TestIdentityStoreRegistersAndUnregistersProjectIdentity(t *testing.T) {
	root := t.TempDir()
	t.Setenv("GHOSTABLE_KEYSTORE", filepath.Join(root, "keys"))

	store, err := NewIdentityStore()
	if err != nil {
		t.Fatal(err)
	}
	repoRoot := filepath.Join(root, "repo")
	identity := domain.LocalIdentityRecord{
		Schema:    domain.LocalIdentitySchema,
		ProjectID: "project-1",
		DeviceID:  "device-1",
	}
	if err := store.RegisterProjectIdentity(identity, "Test Project", repoRoot); err != nil {
		t.Fatal(err)
	}

	entries, err := store.ListProjectIdentities()
	if err != nil {
		t.Fatal(err)
	}
	if len(entries) != 1 {
		t.Fatalf("expected one registered identity, got %#v", entries)
	}
	if entries[0].ProjectID != "project-1" || entries[0].ProjectName != "Test Project" || entries[0].DeviceID != "device-1" {
		t.Fatalf("unexpected registry entry: %#v", entries[0])
	}
	if entries[0].Root != repoRoot {
		t.Fatalf("expected registry root %s, got %s", repoRoot, entries[0].Root)
	}
	if entries[0].Identity != store.Path("project-1") {
		t.Fatalf("expected identity path %s, got %s", store.Path("project-1"), entries[0].Identity)
	}

	if err := store.UnregisterProjectIdentity("project-1"); err != nil {
		t.Fatal(err)
	}
	entries, err = store.ListProjectIdentities()
	if err != nil {
		t.Fatal(err)
	}
	if len(entries) != 0 {
		t.Fatalf("expected registry to be empty, got %#v", entries)
	}
}

func TestIsKeychainItemNotFound(t *testing.T) {
	missingOutputs := []string{
		"security: SecKeychainSearchCopyNext: The specified item could not be found in the keychain.",
		"The specified item could not be found.",
		"security: SecKeychainItemCopyContent: Unknown format in import. (-25300)",
	}
	for _, output := range missingOutputs {
		if !isKeychainItemNotFound(output) {
			t.Fatalf("expected missing keychain item output to be detected: %q", output)
		}
	}

	if isKeychainItemNotFound("security: authorization failed") {
		t.Fatal("did not expect authorization failure to be treated as missing")
	}
}
