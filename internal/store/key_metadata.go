package store

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"strings"

	"github.com/ghostable-dev/beta/internal/domain"
	"github.com/ghostable-dev/beta/internal/security"
)

type keyMetadataUpdate func(*domain.EnvironmentKeyMetadataRecord) error

type keyMetadataRotationRecord struct {
	record domain.EnvironmentKeyMetadataRecord
	note   string
}

const keyMetadataPositionStep int64 = 1000

func (r Repository) keyMetadataDir(env string) string {
	return filepath.Join(r.environmentDir(env), "keys")
}

func (r Repository) keyMetadataPath(env string, key string) string {
	return filepath.Join(r.keyMetadataDir(env), keyPathSegment(key)+".json")
}

func (r Repository) updateKeyMetadata(env string, key string, update keyMetadataUpdate) error {
	if err := r.requireEnvironment(env); err != nil {
		return err
	}
	if err := r.requireWrite(env); err != nil {
		return err
	}
	if err := validateKey(key); err != nil {
		return err
	}
	if err := r.ensureEnvironmentDirs(env); err != nil {
		return err
	}

	record, exists, err := r.readKeyMetadata(env, key)
	if err != nil {
		return err
	}
	if !exists {
		record = r.newKeyMetadataRecord(env, key)
		position, err := r.nextKeyMetadataPosition(env)
		if err != nil {
			return err
		}
		record.Position = position
	}

	if update != nil {
		if err := update(&record); err != nil {
			return err
		}
	}
	return r.writeKeyMetadata(record)
}

func (r Repository) newKeyMetadataRecord(env string, key string) domain.EnvironmentKeyMetadataRecord {
	now := security.Now()
	return domain.EnvironmentKeyMetadataRecord{
		Schema:            domain.KeyMetadataSchema,
		ProjectID:         r.Manifest.ID,
		Environment:       env,
		Key:               key,
		Status:            domain.KeyStatusActive,
		CreatedByDeviceID: r.DeviceID(),
		CreatedAt:         now,
		UpdatedByDeviceID: r.DeviceID(),
		UpdatedAt:         now,
		SignerDeviceID:    r.DeviceID(),
	}
}

func (r Repository) writeKeyMetadata(record domain.EnvironmentKeyMetadataRecord) error {
	if err := validateKeyMetadataRecordShape(record); err != nil {
		return err
	}
	normalizeKeyMetadataDeploy(&record)
	now := security.Now()
	if record.CreatedAt == "" {
		record.CreatedAt = now
	}
	if record.CreatedByDeviceID == "" {
		record.CreatedByDeviceID = r.DeviceID()
	}
	record.UpdatedAt = now
	record.UpdatedByDeviceID = r.DeviceID()
	record.SignerDeviceID = r.DeviceID()
	record.ClientSig = ""
	signature, err := security.SignCanonical(record, r.Identity)
	if err != nil {
		return err
	}
	record.ClientSig = signature
	return writeJSONAtomic(r.keyMetadataPath(record.Environment, record.Key), record, 0o600)
}

func normalizeKeyMetadataDeploy(record *domain.EnvironmentKeyMetadataRecord) {
	if record.Deploy == nil {
		return
	}
	if record.Deploy.LaravelVaporSecret == nil {
		record.Deploy = nil
	}
}

func validateKeyMetadataRecordShape(record domain.EnvironmentKeyMetadataRecord) error {
	if record.Schema != domain.KeyMetadataSchema {
		return fmt.Errorf("key metadata for %s has invalid schema", record.Key)
	}
	if record.ProjectID == "" || record.Environment == "" || record.Key == "" {
		return fmt.Errorf("key metadata is missing its project, environment, or key")
	}
	if err := validateKey(record.Key); err != nil {
		return err
	}
	if err := validateKeyMetadataStatus(record.Status); err != nil {
		return err
	}
	return nil
}

func validateKeyMetadataStatus(status string) error {
	switch status {
	case domain.KeyStatusActive, domain.KeyStatusCommented:
		return nil
	default:
		return fmt.Errorf("invalid key metadata status %q", status)
	}
}

func (r Repository) readKeyMetadata(env string, key string) (domain.EnvironmentKeyMetadataRecord, bool, error) {
	var record domain.EnvironmentKeyMetadataRecord
	path := r.keyMetadataPath(env, key)
	if err := readJSON(path, &record); err != nil {
		if errors.Is(err, os.ErrNotExist) {
			return record, false, nil
		}
		return record, false, err
	}
	if err := r.validateKeyMetadataStorageBinding(record, env, key); err != nil {
		return record, true, err
	}
	return record, true, nil
}

func (r Repository) readEnvironmentKeyMetadataRecords(env string) ([]domain.EnvironmentKeyMetadataRecord, error) {
	entries, err := os.ReadDir(r.keyMetadataDir(env))
	if err != nil {
		if errors.Is(err, os.ErrNotExist) {
			return []domain.EnvironmentKeyMetadataRecord{}, nil
		}
		return nil, err
	}

	seen := map[string]string{}
	records := make([]domain.EnvironmentKeyMetadataRecord, 0, len(entries))
	for _, entry := range entries {
		if entry.IsDir() || !strings.HasSuffix(entry.Name(), ".json") {
			continue
		}

		var record domain.EnvironmentKeyMetadataRecord
		path := filepath.Join(r.keyMetadataDir(env), entry.Name())
		if err := readJSON(path, &record); err != nil {
			return nil, err
		}
		if err := r.validateKeyMetadataStorageBinding(record, env, record.Key); err != nil {
			return nil, err
		}
		expectedName := filepath.Base(r.keyMetadataPath(env, record.Key))
		if entry.Name() != expectedName {
			return nil, fmt.Errorf("key metadata %s is stored in %s but expected %s", record.Key, entry.Name(), expectedName)
		}
		if existingFile, exists := seen[record.Key]; exists {
			return nil, fmt.Errorf("duplicate key metadata for %s in %s and %s", record.Key, existingFile, entry.Name())
		}
		seen[record.Key] = entry.Name()
		records = append(records, record)
	}
	return records, nil
}

func (r Repository) validateKeyMetadataStorageBinding(record domain.EnvironmentKeyMetadataRecord, env string, key string) error {
	if err := validateKeyMetadataRecordShape(record); err != nil {
		return err
	}
	if record.ProjectID != r.Manifest.ID || record.Environment != env || record.Key != key {
		return fmt.Errorf("key metadata %s is not bound to its Ghostable storage path", key)
	}
	return nil
}

func (r Repository) keyMetadataByKey(env string) (map[string]domain.EnvironmentKeyMetadataRecord, error) {
	records, err := r.readEnvironmentKeyMetadataRecords(env)
	if err != nil {
		return nil, err
	}
	metadata := make(map[string]domain.EnvironmentKeyMetadataRecord, len(records))
	for _, record := range records {
		metadata[record.Key] = record
	}
	return metadata, nil
}

func (r Repository) nextKeyMetadataPosition(env string) (int64, error) {
	records, err := r.readEnvironmentKeyMetadataRecords(env)
	if err != nil {
		return 0, err
	}
	var position int64
	for _, record := range records {
		if record.Position > position {
			position = record.Position
		}
	}
	next := position + keyMetadataPositionStep
	if next == 0 {
		return keyMetadataPositionStep, nil
	}
	return next, nil
}

func (r Repository) keyMetadataOrder(env string) ([]string, error) {
	records, err := r.readEnvironmentKeyMetadataRecords(env)
	if err != nil {
		return nil, err
	}
	items := make([]domain.EnvironmentKeyMetadataRecord, 0, len(records))
	for _, record := range records {
		if record.Position == 0 {
			continue
		}
		if err := r.verifyKeyMetadata(record); err != nil {
			return nil, err
		}
		items = append(items, record)
	}
	sort.Slice(items, func(i, j int) bool {
		if items[i].Position == items[j].Position {
			return items[i].Key < items[j].Key
		}
		return items[i].Position < items[j].Position
	})
	order := make([]string, 0, len(items))
	for _, item := range items {
		order = append(order, item.Key)
	}
	return order, nil
}

func (r Repository) readKeyMetadataRotationRecords(env string, envKey []byte) ([]keyMetadataRotationRecord, error) {
	records, err := r.readEnvironmentKeyMetadataRecords(env)
	if err != nil {
		return nil, err
	}
	rotations := make([]keyMetadataRotationRecord, 0, len(records))
	for _, record := range records {
		if record.EncryptedNote == nil {
			continue
		}
		if err := r.verifyKeyMetadata(record); err != nil {
			return nil, err
		}
		note, err := decryptKeyMetadataNoteWithKey(record, envKey)
		if err != nil {
			return nil, err
		}
		rotations = append(rotations, keyMetadataRotationRecord{
			record: record,
			note:   note,
		})
	}
	return rotations, nil
}

func buildRotatedKeyMetadataRecord(rotation keyMetadataRotationRecord, envKey []byte) (domain.EnvironmentKeyMetadataRecord, error) {
	record := rotation.record
	encryptedNote, err := encryptKeyMetadataNoteWithKey(record.ProjectID, record.Environment, record.Key, rotation.note, envKey)
	if err != nil {
		return domain.EnvironmentKeyMetadataRecord{}, err
	}
	record.EncryptedNote = encryptedNote
	return record, nil
}

func (r Repository) removeKeyMetadata(env string, key string) error {
	if err := os.Remove(r.keyMetadataPath(env, key)); err != nil && !errors.Is(err, os.ErrNotExist) {
		return err
	}
	return nil
}

func (r Repository) rewriteKeyMetadataEnvironment(env string, source string, target string) error {
	entries, err := os.ReadDir(r.keyMetadataDir(env))
	if err != nil {
		if errors.Is(err, os.ErrNotExist) {
			return nil
		}
		return err
	}
	for _, entry := range entries {
		if entry.IsDir() || !strings.HasSuffix(entry.Name(), ".json") {
			continue
		}
		var record domain.EnvironmentKeyMetadataRecord
		path := filepath.Join(r.keyMetadataDir(env), entry.Name())
		if err := readJSON(path, &record); err != nil {
			return err
		}
		if record.Environment != source {
			continue
		}
		record.Environment = target
		if err := r.writeKeyMetadata(record); err != nil {
			return err
		}
		if nextPath := r.keyMetadataPath(target, record.Key); nextPath != path {
			_ = os.Remove(path)
		}
	}
	return nil
}

func (r Repository) verifyKeyMetadata(record domain.EnvironmentKeyMetadataRecord) error {
	if err := validateKeyMetadataRecordShape(record); err != nil {
		return err
	}
	if record.ProjectID != r.Manifest.ID {
		return fmt.Errorf("key metadata %s is not bound to this project", record.Key)
	}
	deviceID := record.SignerDeviceID
	if deviceID == "" {
		deviceID = record.UpdatedByDeviceID
	}
	if deviceID == "" || record.ClientSig == "" {
		return fmt.Errorf("key metadata %s is missing a valid signature", record.Key)
	}
	device, err := r.readDevice(deviceID)
	if err != nil {
		return err
	}
	policy, err := r.readVerifiedPolicyMetadata()
	if err != nil {
		return err
	}
	if !canWrite(policy, record.Environment, deviceID) {
		return fmt.Errorf("key metadata %s was signed by a device without write access", record.Key)
	}
	if !security.VerifyCanonical(record, device.SigningKey.PublicKey, record.ClientSig) {
		return fmt.Errorf("key metadata %s has an invalid device signature", record.Key)
	}
	return nil
}

func (r Repository) VerifyKeyMetadataFile(path string) error {
	resolvedPath := r.resolveProjectPath(path)
	var record domain.EnvironmentKeyMetadataRecord
	if err := readJSON(resolvedPath, &record); err != nil {
		return err
	}
	expectedPath, err := filepath.Abs(r.keyMetadataPath(record.Environment, record.Key))
	if err != nil {
		return err
	}
	actualPath, err := filepath.Abs(resolvedPath)
	if err != nil {
		return err
	}
	if actualPath != expectedPath {
		return fmt.Errorf("key metadata %s is not bound to its Ghostable storage path", record.Key)
	}
	return r.verifyKeyMetadata(record)
}

func (r Repository) encryptKeyMetadataNote(env string, key string, note string) (*domain.EncryptedPayload, error) {
	if note == "" {
		return nil, nil
	}
	_, envKey, err := r.loadEnvironmentKey(env)
	if err != nil {
		return nil, err
	}
	return encryptKeyMetadataNoteWithKey(r.Manifest.ID, env, key, note, envKey)
}

func encryptKeyMetadataNoteWithKey(projectID string, env string, key string, note string, envKey []byte) (*domain.EncryptedPayload, error) {
	if note == "" {
		return nil, nil
	}
	noteKey, _, err := security.DeriveValueKeys(envKey, keyMetadataNoteScope(projectID, env))
	if err != nil {
		return nil, err
	}
	payload, err := security.EncryptXChaCha(noteKey, []byte(note), keyMetadataNoteAAD(projectID, env, key))
	if err != nil {
		return nil, err
	}
	return &payload, nil
}

func (r Repository) decryptKeyMetadataNote(record domain.EnvironmentKeyMetadataRecord) (string, error) {
	if record.EncryptedNote == nil {
		return "", nil
	}
	_, envKey, err := r.loadEnvironmentKey(record.Environment)
	if err != nil {
		return "", err
	}
	return decryptKeyMetadataNoteWithKey(record, envKey)
}

func decryptKeyMetadataNoteWithKey(record domain.EnvironmentKeyMetadataRecord, envKey []byte) (string, error) {
	if record.EncryptedNote == nil {
		return "", nil
	}
	noteKey, _, err := security.DeriveValueKeys(envKey, keyMetadataNoteScope(record.ProjectID, record.Environment))
	if err != nil {
		return "", err
	}
	plaintext, err := security.DecryptXChaCha(noteKey, *record.EncryptedNote, keyMetadataNoteAAD(record.ProjectID, record.Environment, record.Key))
	if err != nil {
		return "", err
	}
	return string(plaintext), nil
}

func keyMetadataNoteScope(projectID string, env string) string {
	return domain.GhostableOrgScope + "/" + projectID + "/" + env + "/key-metadata-notes"
}

func keyMetadataNoteAAD(projectID string, env string, key string) []byte {
	return []byte(strings.Join([]string{domain.GhostableOrgScope, projectID, env, key, "note"}, "\n"))
}
