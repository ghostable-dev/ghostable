package app

import (
	"strings"
	"testing"
)

func TestRenderGhostableBanner(t *testing.T) {
	banner := renderGhostableBanner()

	if !strings.Contains(banner, "\x1b[38;2;70;185;168m") {
		t.Fatal("banner should include the first gradient color")
	}
	if !strings.Contains(banner, "▗▄▄▖▗▖ ▗▖ ▗▄▖") {
		t.Fatal("banner should include the Ghostable wordmark")
	}
	if strings.Contains(banner, "   \n") {
		t.Fatal("banner should trim trailing spaces from visible lines")
	}
}
