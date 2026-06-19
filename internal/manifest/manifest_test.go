package manifest

import (
	"bytes"
	"strings"
	"testing"

	"github.com/ghostable-dev/beta/internal/domain"
)

func TestManifestScanSettingsRoundTrip(t *testing.T) {
	project := domain.ProjectManifest{
		Schema:       domain.ProjectSchema,
		ID:           "project-1",
		Name:         "Example",
		ActivityMode: domain.DefaultActivity,
		AuditEnvs:    []string{"production"},
		Environments: map[string]domain.Environment{
			"default": {Name: "default", Type: "local"},
		},
		ScanLevel:   "strict",
		ScanIgnores: []string{"fixtures/**", "storage/*.key"},
	}
	var buffer bytes.Buffer

	if err := Write(&buffer, project); err != nil {
		t.Fatal(err)
	}

	content := buffer.String()
	if !strings.Contains(content, "  level: strict") {
		t.Fatalf("expected scan level in manifest:\n%s", content)
	}
	if !strings.Contains(content, "    - fixtures/**") {
		t.Fatalf("expected scan ignores in manifest:\n%s", content)
	}

	parsed, err := Read(strings.NewReader(content))
	if err != nil {
		t.Fatal(err)
	}
	if parsed.ScanLevel != "strict" {
		t.Fatalf("expected strict scan level, got %q", parsed.ScanLevel)
	}
	if len(parsed.ScanIgnores) != 2 || parsed.ScanIgnores[0] != "fixtures/**" || parsed.ScanIgnores[1] != "storage/*.key" {
		t.Fatalf("unexpected scan ignores: %#v", parsed.ScanIgnores)
	}
}
