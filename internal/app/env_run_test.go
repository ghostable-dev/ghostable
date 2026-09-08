package app

import (
	"fmt"
	"os"
	"reflect"
	"runtime"
	"strings"
	"testing"
)

func TestCommandEnvironmentLerdPassthrough(t *testing.T) {
	t.Setenv("LERD_PASSTHROUGH_ENV", "SHELL_ONLY,SECRET_*")
	t.Setenv("SHELL_ONLY", "from-shell")
	t.Setenv("APP_NAME", "from-shell")

	tests := []struct {
		name   string
		values map[string]string
		want   string
	}{
		{
			name: "exact names including empty and multiline values",
			values: map[string]string{
				"SECRET_TOKEN": "synthetic-value, with spaces=and\na newline",
				"APP_NAME":     "Ghostable",
				"EMPTY_VALUE":  "",
			},
			want: "APP_NAME,EMPTY_VALUE,SECRET_TOKEN",
		},
		{
			name: "stored control variable cannot broaden the list",
			values: map[string]string{
				"APP_NAME":             "Ghostable",
				"LERD_PASSTHROUGH_ENV": "*",
			},
			want: "APP_NAME",
		},
		{
			name:   "no injected keys clears inherited list",
			values: map[string]string{},
			want:   "",
		},
		{
			name:   "control variable alone forwards nothing",
			values: map[string]string{"LERD_PASSTHROUGH_ENV": "*"},
			want:   "",
		},
	}
	for _, tt := range tests {
		for _, inherit := range []bool{true, false} {
			t.Run(fmt.Sprintf("%s/inherit=%t", tt.name, inherit), func(t *testing.T) {
				env := commandEnvironment(inherit, tt.values)
				got := map[string]string{}
				for _, entry := range env {
					key, value, _ := strings.Cut(entry, "=")
					if _, exists := got[key]; exists {
						t.Fatalf("duplicate child environment key %s", key)
					}
					got[key] = value
				}
				if value, ok := got["LERD_PASSTHROUGH_ENV"]; !ok || value != tt.want {
					t.Fatalf("Lerd names = %q (present=%t), want %q", value, ok, tt.want)
				}
				for key, value := range tt.values {
					if key == "LERD_PASSTHROUGH_ENV" {
						continue
					}
					if actual, ok := got[key]; !ok || actual != value {
						t.Fatalf("injected value for %s was changed or omitted", key)
					}
				}
				if _, ok := got["SHELL_ONLY"]; ok != inherit {
					t.Fatalf("shell-only value present=%t, want %t", ok, inherit)
				}
				if next := commandEnvironment(inherit, tt.values); !reflect.DeepEqual(env, next) {
					t.Fatal("child environment is not stable across invocations")
				}
			})
		}
	}
	if os.Getenv("LERD_PASSTHROUGH_ENV") != "SHELL_ONLY,SECRET_*" {
		t.Fatal("parent environment was modified")
	}
}

func TestCommandEnvironmentLerdControlVariableCase(t *testing.T) {
	t.Setenv("lerd_passthrough_env", "SHELL_ONLY")
	values := map[string]string{"APP_NAME": "Ghostable", "lerd_passthrough_env": "*"}
	env := commandEnvironment(true, values)
	want := "LERD_PASSTHROUGH_ENV=APP_NAME,lerd_passthrough_env"
	if runtime.GOOS == "windows" {
		want = "LERD_PASSTHROUGH_ENV=APP_NAME"
	}
	found := false
	for _, entry := range env {
		if entry == want {
			found = true
		}
		if runtime.GOOS == "windows" && strings.HasPrefix(entry, "lerd_passthrough_env=") {
			t.Fatal("case-insensitive control variable could override the generated list")
		}
	}
	if !found {
		t.Fatalf("child environment did not contain %q", want)
	}
}
