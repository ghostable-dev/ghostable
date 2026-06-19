package crypto

import (
	"crypto/aes"
	"crypto/cipher"
	"crypto/rand"
	"encoding/base64"
	"fmt"
	"io"

	"github.com/ghostable-dev/beta/internal/domain"
)

const (
	AlgorithmAES256GCM = "aes-256-gcm"
	keySizeBytes       = 32
	nonceSizeBytes     = 12
)

func NewKey() ([]byte, error) {
	key := make([]byte, keySizeBytes)
	if _, err := io.ReadFull(rand.Reader, key); err != nil {
		return nil, err
	}
	return key, nil
}

func Encrypt(key []byte, plaintext []byte, aad []byte) (domain.EncryptedPayload, error) {
	gcm, err := newGCM(key)
	if err != nil {
		return domain.EncryptedPayload{}, err
	}

	nonce := make([]byte, nonceSizeBytes)
	if _, err := io.ReadFull(rand.Reader, nonce); err != nil {
		return domain.EncryptedPayload{}, err
	}

	ciphertext := gcm.Seal(nil, nonce, plaintext, aad)
	return domain.EncryptedPayload{
		Alg:           AlgorithmAES256GCM,
		NonceB64:      base64.StdEncoding.EncodeToString(nonce),
		CiphertextB64: base64.StdEncoding.EncodeToString(ciphertext),
	}, nil
}

func Decrypt(key []byte, payload domain.EncryptedPayload, aad []byte) ([]byte, error) {
	if payload.Alg != AlgorithmAES256GCM {
		return nil, fmt.Errorf("unsupported encrypted payload algorithm %q", payload.Alg)
	}

	nonce, err := base64.StdEncoding.DecodeString(payload.NonceB64)
	if err != nil {
		return nil, fmt.Errorf("invalid encrypted payload nonce: %w", err)
	}

	ciphertext, err := base64.StdEncoding.DecodeString(payload.CiphertextB64)
	if err != nil {
		return nil, fmt.Errorf("invalid encrypted payload ciphertext: %w", err)
	}

	gcm, err := newGCM(key)
	if err != nil {
		return nil, err
	}

	plaintext, err := gcm.Open(nil, nonce, ciphertext, aad)
	if err != nil {
		return nil, fmt.Errorf("unable to decrypt value with the local project key")
	}

	return plaintext, nil
}

func newGCM(key []byte) (cipher.AEAD, error) {
	if len(key) != keySizeBytes {
		return nil, fmt.Errorf("project key must be %d bytes", keySizeBytes)
	}

	block, err := aes.NewCipher(key)
	if err != nil {
		return nil, err
	}

	return cipher.NewGCM(block)
}
