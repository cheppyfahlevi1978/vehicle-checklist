# Uji API eArsip

## Login

```bash
curl -X POST https://arsip.ias4u.my.id/api/mobile/v1/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"login":"admin@ias4u.my.id","password":"PASSWORD_ANDA","device_name":"Android Test"}'
```

Respons wajib memiliki salah satu atau seluruh field berikut:

```text
token
access_token
data.token
data.access_token
```

## Profil

```bash
curl https://arsip.ias4u.my.id/api/mobile/v1/me \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN_ANDA'
```

## Upload arsip

```bash
curl -X POST https://arsip.ias4u.my.id/api/mobile/v1/archives \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN_ANDA' \
  -F 'classification_id=1' \
  -F 'type=GENERAL' \
  -F 'title=Dokumen Percobaan' \
  -F 'security_level=INTERNAL' \
  -F 'file=@dokumen.pdf'
```
