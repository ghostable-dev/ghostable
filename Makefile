.PHONY: dev build fmt check-fmt test vet security-check check clean

dev:
	mkdir -p ./tmp
	watchexec -e go -- go build -o ./tmp/ghostable ./cmd/ghostable

build:
	mkdir -p ./tmp
	go build -o ./tmp/ghostable ./cmd/ghostable

fmt:
	gofmt -w cmd internal

check-fmt:
	@if [ -n "$$(gofmt -l cmd internal)" ]; then \
		gofmt -l cmd internal; \
		exit 1; \
	fi

test:
	go test ./...

vet:
	go vet ./...

security-check:
	go test -cover ./internal/crypto ./internal/security ./internal/userpresence
	go run golang.org/x/vuln/cmd/govulncheck@v1.5.0 ./...

check: check-fmt test vet

clean:
	rm -rf ./tmp
