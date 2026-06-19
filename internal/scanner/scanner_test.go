package scanner

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestScanFindsAndRedactsSecrets(t *testing.T) {
	root := t.TempDir()
	path := filepath.Join(root, "config.js")
	content := []byte(`const OPENAI_API_KEY = "sk-proj-abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";`)
	if err := os.WriteFile(path, content, 0o644); err != nil {
		t.Fatal(err)
	}

	result, err := Scan(Options{Root: root})
	if err != nil {
		t.Fatal(err)
	}
	if len(result.Findings) == 0 {
		t.Fatal("expected a secret finding")
	}
	if result.Findings[0].Value != "" {
		t.Fatal("expected value to be redacted by default")
	}
	if result.Findings[0].Redacted == "" {
		t.Fatal("expected redacted value")
	}
}

func TestInspectLineFindsMajorAIProviderKeys(t *testing.T) {
	cases := []struct {
		name  string
		value string
		kind  string
	}{
		{name: "anthropic", value: syntheticCredential("sk-ant-api03-", 48), kind: "Anthropic API key"},
		{name: "openrouter", value: syntheticCredential("sk-or-v1-", 48), kind: "OpenRouter API key"},
		{name: "google", value: syntheticCredential("AIza", 35), kind: "Google API key"},
		{name: "google auth", value: syntheticCredential("AQ.", 48), kind: "Google AI auth key"},
		{name: "hugging face", value: syntheticCredential("hf_", 34), kind: "Hugging Face token"},
		{name: "groq", value: syntheticCredential("gsk_", 48), kind: "Groq API key"},
		{name: "perplexity", value: syntheticCredential("pplx-", 48), kind: "Perplexity API key"},
		{name: "replicate", value: syntheticCredential("r8_", 37), kind: "Replicate API token"},
		{name: "xai", value: syntheticCredential("xai-", 48), kind: "xAI API key"},
		{name: "cerebras", value: syntheticCredential("csk_", 48), kind: "Cerebras API key"},
	}

	for _, tc := range cases {
		t.Run(tc.name, func(t *testing.T) {
			findings := inspectLine("config.env", 1, "AI_PROVIDER_KEY="+tc.value, "relaxed", true)
			if len(findings) != 1 {
				t.Fatalf("expected one finding, got %#v", findings)
			}
			if findings[0].Kind != tc.kind {
				t.Fatalf("expected %q, got %#v", tc.kind, findings[0])
			}
			if findings[0].Value != tc.value {
				t.Fatalf("expected visible value %q, got %q", tc.value, findings[0].Value)
			}
		})
	}
}

func TestScanIgnoresJavaScriptCodeExpressions(t *testing.T) {
	root := t.TempDir()
	path := filepath.Join(root, "ui.js")
	content := []byte(`const elements = { accessAutomationToken: document.querySelector("#access-automation-token") };`)
	if err := os.WriteFile(path, content, 0o644); err != nil {
		t.Fatal(err)
	}

	result, err := Scan(Options{Root: root})
	if err != nil {
		t.Fatal(err)
	}
	if len(result.Findings) != 0 {
		t.Fatalf("expected no findings for code expressions, got %#v", result.Findings)
	}
}

func TestScanLevelRelaxedOnlyReportsHighConfidenceFindings(t *testing.T) {
	root := t.TempDir()
	if err := os.WriteFile(filepath.Join(root, "config.env"), []byte("APP_SECRET=abc123abc123abc123abc123"), 0o644); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(root, "token.txt"), []byte("sk-proj-abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0o644); err != nil {
		t.Fatal(err)
	}

	result, err := Scan(Options{Root: root, Level: "relaxed"})
	if err != nil {
		t.Fatal(err)
	}
	if result.Level != "relaxed" {
		t.Fatalf("expected relaxed level, got %q", result.Level)
	}
	if len(result.Findings) != 1 {
		t.Fatalf("expected one high confidence finding, got %#v", result.Findings)
	}
	if result.Findings[0].Confidence != "high" {
		t.Fatalf("expected high confidence finding, got %#v", result.Findings[0])
	}
}

func TestScanLevelStrictReportsLowerConfidenceAssignments(t *testing.T) {
	root := t.TempDir()
	if err := os.WriteFile(filepath.Join(root, "config.env"), []byte("APP_SECRET=abc123!x"), 0o644); err != nil {
		t.Fatal(err)
	}

	standard, err := Scan(Options{Root: root, Level: "standard"})
	if err != nil {
		t.Fatal(err)
	}
	if len(standard.Findings) != 0 {
		t.Fatalf("expected standard scan to skip short assignment, got %#v", standard.Findings)
	}

	strict, err := Scan(Options{Root: root, Level: "strict"})
	if err != nil {
		t.Fatal(err)
	}
	if len(strict.Findings) != 1 {
		t.Fatalf("expected strict scan to report assignment, got %#v", strict.Findings)
	}
	if strict.Findings[0].Confidence != "low" {
		t.Fatalf("expected low confidence strict finding, got %#v", strict.Findings[0])
	}
}

func TestScanHonorsIgnores(t *testing.T) {
	root := t.TempDir()
	if err := os.MkdirAll(filepath.Join(root, "fixtures"), 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(root, "fixtures", "secret.txt"), []byte("AWS_SECRET_ACCESS_KEY=abc123abc123abc123abc123"), 0o644); err != nil {
		t.Fatal(err)
	}

	result, err := Scan(Options{Root: root, Ignores: []string{"fixtures/**"}})
	if err != nil {
		t.Fatal(err)
	}
	if len(result.Findings) != 0 {
		t.Fatalf("expected ignored file to have no findings: %#v", result.Findings)
	}
}

func TestScanHonorsNestedDirectoryNameIgnores(t *testing.T) {
	root := t.TempDir()
	nested := filepath.Join(root, "app", "node_modules", "package")
	if err := os.MkdirAll(nested, 0o755); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(nested, "secret.txt"), []byte("OPENAI_API_KEY=sk-proj-abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0o644); err != nil {
		t.Fatal(err)
	}

	result, err := Scan(Options{Root: root, Ignores: []string{"node_modules"}})
	if err != nil {
		t.Fatal(err)
	}
	if len(result.Findings) != 0 {
		t.Fatalf("expected nested node_modules to have no findings: %#v", result.Findings)
	}
}

func TestScanSkipsSymlinkedDirectories(t *testing.T) {
	root := t.TempDir()
	target := filepath.Join(root, "target")
	if err := os.MkdirAll(target, 0o755); err != nil {
		t.Fatal(err)
	}
	link := filepath.Join(root, "linked-dir")
	if err := os.Symlink(target, link); err != nil {
		t.Skipf("symlink not supported: %v", err)
	}

	result, err := Scan(Options{Root: root})
	if err != nil {
		t.Fatal(err)
	}
	if result.Scanned != 0 {
		t.Fatalf("expected no scanned files, got %#v", result)
	}
}

func syntheticCredential(prefix string, bodyLength int) string {
	body := "AbCdEfGhIjKlMnOpQrStUvWxYz0123456789"
	repeated := strings.Repeat(body, bodyLength/len(body)+1)
	return prefix + repeated[:bodyLength]
}
