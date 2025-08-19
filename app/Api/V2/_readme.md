# API v2 Layer

Version 2 Request Mappers and Presenters reside here.

Organize per domain within `app/Api/V2/{Domain}/Requests` and
`app/Api/V2/{Domain}/Presenters`.

`ApplyApiVersion` will later resolve these when `api.version:v2` is applied.

These classes adapt domain models to the evolving public API surface.
