package main

import (
	"os"

	"github.com/ghostable-dev/ghostable/internal/app"
)

func main() {
	os.Exit(app.Run(os.Args, os.Stdin, os.Stdout, os.Stderr))
}
