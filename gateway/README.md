# WA Gateway VPS

## Persyaratan

- Ubuntu 22.04/24.04
- Node.js 20 atau lebih baru
- Chromium/Google Chrome dan dependency Puppeteer
- PM2
- Nginx + Certbot
- RAM disarankan minimal 2 GB untuk satu sesi

## Instalasi

```bash
sudo mkdir -p /opt/wa-gateway
sudo chown -R $USER:$USER /opt/wa-gateway
# salin isi folder gateway ke /opt/wa-gateway
cd /opt/wa-gateway
cp .env.example .env
npm install
npm install -g pm2
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup
```

Pasang konfigurasi Nginx dan SSL untuk `gateway-wa.ias4u.my.id`. Jangan membuka port 21465 langsung ke internet. Gateway dilindungi `X-Gateway-Key`, tetapi pembatasan firewall tetap diperlukan.
