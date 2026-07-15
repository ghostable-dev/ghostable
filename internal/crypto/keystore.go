package crypto

import (
	"encoding/base64"
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"runtime"
	"strings"
	"time"

	"github.com/ghostable-dev/ghostable/internal/domain"
)

type KeyStore struct {
	root string
}

func NewKeyStore() (KeyStore, error) {
	configDir, err := os.UserConfigDir()
	if err != nil {
		return KeyStore{}, err
	}

	root := filepath.Join(configDir, "ghostable", "keys")
	if override := strings.TrimSpace(os.Getenv("GHOSTABLE_KEYSTORE")); override != "" {
		root = override
	}

	return KeyStore{root: root}, nil
}

func (s KeyStore) Load(projectID string) (domain.LocalKeyRecord, []byte, error) {
	path := s.path(projectID)
	content, err := os.ReadFile(path)
	if err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}

	var record domain.LocalKeyRecord
	if err := json.Unmarshal(content, &record); err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}
	if record.Schema != domain.LocalKeySchema {
		return domain.LocalKeyRecord{}, nil, fmt.Errorf("unsupported local key schema %q", record.Schema)
	}
	if record.ProjectID != projectID {
		return domain.LocalKeyRecord{}, nil, fmt.Errorf("local key project id mismatch")
	}

	key, err := base64.StdEncoding.DecodeString(record.KeyB64)
	if err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}
	if len(key) != keySizeBytes {
		return domain.LocalKeyRecord{}, nil, fmt.Errorf("local key is not %d bytes", keySizeBytes)
	}

	return record, key, nil
}

func (s KeyStore) Create(projectID string, deviceID string) (domain.LocalKeyRecord, []byte, error) {
	if err := os.MkdirAll(s.root, 0o700); err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}
	if err := ensurePrivateDir(s.root); err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}

	key, err := NewKey()
	if err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}

	record := domain.LocalKeyRecord{
		Schema:    domain.LocalKeySchema,
		ProjectID: projectID,
		DeviceID:  deviceID,
		KeyB64:    base64.StdEncoding.EncodeToString(key),
		CreatedAt: time.Now().UTC(),
	}

	content, err := json.MarshalIndent(record, "", "  ")
	if err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}
	content = append(content, '\n')

	path := s.path(projectID)
	if err := os.WriteFile(path, content, 0o600); err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}
	if err := ensurePrivateFile(path); err != nil {
		return domain.LocalKeyRecord{}, nil, err
	}

	return record, key, nil
}

func (s KeyStore) LoadOrCreate(projectID string, deviceID string) (domain.LocalKeyRecord, []byte, bool, error) {
	record, key, err := s.Load(projectID)
	if err == nil {
		return record, key, false, nil
	}
	if !os.IsNotExist(err) {
		return domain.LocalKeyRecord{}, nil, false, err
	}

	record, key, err = s.Create(projectID, deviceID)
	return record, key, true, err
}

func (s KeyStore) Delete(projectID string) error {
	err := os.Remove(s.path(projectID))
	if err != nil && !os.IsNotExist(err) {
		return err
	}
	return nil
}

func (s KeyStore) Path(projectID string) string {
	return s.path(projectID)
}

func (s KeyStore) path(projectID string) string {
	safe := strings.NewReplacer("/", "_", "\\", "_", ":", "_").Replace(projectID)
	return filepath.Join(s.root, safe+".json")
}

func ensurePrivateDir(path string) error {
	info, err := os.Stat(path)
	if err != nil {
		return err
	}
	if !info.IsDir() {
		return fmt.Errorf("%s is not a directory", path)
	}
	if runtime.GOOS == "windows" {
		return nil
	}
	if info.Mode().Perm()&0o077 != 0 {
		return os.Chmod(path, 0o700)
	}
	return nil
}

func ensurePrivateFile(path string) error {
	info, err := os.Stat(path)
	if err != nil {
		return err
	}
	if runtime.GOOS == "windows" {
		return nil
	}
	if info.Mode().Perm()&0o077 != 0 {
		return os.Chmod(path, 0o600)
	}
	return nil
}
