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

func helperProcessArgs() []string {
	for index, arg := range os.Args {
		if arg == "--" {
			return os.Args[index+1:]
		}
	}
	return nil
}

func appendTextFileForTest(path string, text string) error {
	file, err := os.OpenFile(path, os.O_CREATE|os.O_WRONLY|os.O_APPEND, 0o600)
	if err != nil {
		return err
	}
	defer file.Close()
	_, err = file.WriteString(text)
	return err
}
