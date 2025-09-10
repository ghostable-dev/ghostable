# API v1 Layer

Per-domain Request Mappers and Presenters for API version 1 live below this
directory.

Place classes under `app/Api/V1/{Domain}/Requests` and
`app/Api/V1/{Domain}/Presenters`.

The `ApplyApiVersion` middleware will later choose these implementations when
`api.version:v1` is used.

Keep these classes focused on shaping HTTP input/output and leave domain logic
to the core modules.
