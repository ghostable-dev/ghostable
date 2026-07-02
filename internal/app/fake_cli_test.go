package app

import (
	"os"
	"path/filepath"
	"runtime"
	"testing"
)

func writeFakeExecutable(t *testing.T, dir string, name string, unixScript string, windowsBatch string) string {
	t.Helper()

	filename := name
	content := unixScript
	if runtime.GOOS == "windows" {
		filename = name + ".bat"
		content = windowsBatch
	}
	if content == "" {
		if runtime.GOOS == "windows" {
			content = "@echo off\r\nexit /b 0\r\n"
		} else {
			content = "#!/bin/sh\nexit 0\n"
		}
	}

	path := filepath.Join(dir, filename)
	if err := os.WriteFile(path, []byte(content), 0o700); err != nil {
		t.Fatal(err)
	}
	return path
}

func prependPathForTest(t *testing.T, dir string) {
	t.Helper()
	t.Setenv("PATH", dir+string(os.PathListSeparator)+os.Getenv("PATH"))
}
