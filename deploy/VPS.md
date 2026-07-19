# Deployment Gateway di VPS 103.77.78.76

Gunakan subdomain `gateway-wa.ias4u.my.id` yang menunjuk ke VPS. Instal Node.js 20+, Chrome/Chromium, PM2, Nginx, dan Certbot. Salin folder `gateway` ke `/opt/wa-gateway`, isi `.env`, lalu jalankan PM2.

Firewall:

- buka 80/443;
- port 21465 hanya bind ke `127.0.0.1`;
- jangan mengekspos endpoint gateway tanpa Nginx dan API key.

Setelah Nginx aktif:

```bash
sudo certbot --nginx -d gateway-wa.ias4u.my.id
```

Uji:

```bash
curl https://gateway-wa.ias4u.my.id/health
```
