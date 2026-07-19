import 'dotenv/config';
import crypto from 'node:crypto';
import fs from 'node:fs';
import express from 'express';
import helmet from 'helmet';
import rateLimit from 'express-rate-limit';
import axios from 'axios';
import pino from 'pino';
import wppconnect from '@wppconnect-team/wppconnect';

const logger = pino({ level: process.env.LOG_LEVEL || 'info' });
const app = express();
const sessions = new Map();
const states = new Map();
const qrCodes = new Map();
const sessionDir = process.env.SESSION_DIR || './.sessions';
fs.mkdirSync(sessionDir, { recursive: true });

app.use(helmet());
app.use(express.json({ limit: '12mb' }));
app.use(rateLimit({ windowMs: 60_000, limit: 120, standardHeaders: true, legacyHeaders: false }));

app.get('/health', (_req, res) => res.json({ ok: true, service: 'ias4u-wa-gateway', sessions: sessions.size }));

app.use((req, res, next) => {
  const expected = process.env.GATEWAY_KEY || '';
  const provided = req.header('X-Gateway-Key') || '';
  if (!expected || !crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(provided.padEnd(expected.length).slice(0, expected.length)))) {
    return res.status(401).json({ success: false, message: 'Unauthorized gateway request' });
  }
  next();
});

function validSession(value) {
  return typeof value === 'string' && /^[a-zA-Z0-9_-]{1,80}$/.test(value);
}

function normalizePhone(phone) {
  const digits = String(phone || '').replace(/\D/g, '');
  if (!/^[0-9]{8,16}$/.test(digits)) throw new Error('Nomor WhatsApp tidak valid');
  return `${digits}@c.us`;
}

async function postWebhook(event, session, payload) {
  const url = process.env.WEBHOOK_URL;
  const secret = process.env.WEBHOOK_SECRET || '';
  if (!url || !secret) return;
  const body = JSON.stringify({ event, session, payload });
  const signature = crypto.createHmac('sha256', secret).update(body).digest('hex');
  try {
    await axios.post(url, body, { headers: { 'Content-Type': 'application/json', 'X-Gateway-Signature': signature }, timeout: 15000 });
  } catch (error) {
    logger.error({ err: error.message, event, session }, 'Webhook gagal');
  }
}

async function startSession(session) {
  if (sessions.has(session)) return { status: states.get(session) || 'CONNECTED' };
  states.set(session, 'STARTING');

  const client = await wppconnect.create({
    session,
    folderNameToken: sessionDir,
    headless: String(process.env.HEADLESS || 'true') === 'true',
    autoClose: 0,
    catchQR: (base64Qr) => {
      qrCodes.set(session, base64Qr);
      states.set(session, 'QRCODE');
      postWebhook('session.status', session, { status: 'QRCODE' });
    },
    statusFind: (status) => {
      states.set(session, String(status).toUpperCase());
      postWebhook('session.status', session, { status: String(status).toUpperCase() });
    },
  });

  sessions.set(session, client);
  states.set(session, 'CONNECTED');
  qrCodes.delete(session);

  client.onMessage((message) => {
    const phone = String(message.from || '').replace('@c.us', '');
    postWebhook('message.received', session, {
      id: message.id?._serialized || message.id || null,
      phone,
      type: message.type || 'text',
      body: message.body || '',
      timestamp: message.timestamp || Date.now(),
    });
  });

  postWebhook('session.status', session, { status: 'CONNECTED' });
  return { status: 'CONNECTED' };
}

app.post('/sessions', async (req, res) => {
  try {
    const session = req.body.session;
    if (!validSession(session)) return res.status(422).json({ success: false, message: 'Session tidak valid' });
    const result = await startSession(session);
    res.json({ success: true, session, ...result });
  } catch (error) {
    logger.error({ err: error.message }, 'Gagal memulai sesi');
    res.status(500).json({ success: false, message: error.message });
  }
});

app.get('/sessions/:session/status', (req, res) => {
  const session = req.params.session;
  res.json({ success: true, session, status: states.get(session) || 'DISCONNECTED' });
});

app.get('/sessions/:session/qr', (req, res) => {
  const session = req.params.session;
  res.json({ success: true, session, status: states.get(session) || 'DISCONNECTED', qr: qrCodes.get(session) || null });
});

app.post('/sessions/:session/send-text', async (req, res) => {
  try {
    const { phone, message, consentConfirmed } = req.body;
    if (consentConfirmed !== true) return res.status(422).json({ success: false, message: 'Persetujuan penerima wajib dikonfirmasi' });
    if (typeof message !== 'string' || message.length < 1 || message.length > 4096) return res.status(422).json({ success: false, message: 'Pesan tidak valid' });
    const client = sessions.get(req.params.session);
    if (!client) return res.status(409).json({ success: false, message: 'Sesi belum terhubung' });
    const result = await client.sendText(normalizePhone(phone), message);
    const messageId = result?.id?._serialized || result?.id || null;
    res.json({ success: true, status: 'SENT', messageId });
  } catch (error) {
    logger.error({ err: error.message }, 'Kirim pesan gagal');
    res.status(500).json({ success: false, status: 'FAILED', message: error.message });
  }
});

app.post('/sessions/:session/logout', async (req, res) => {
  const client = sessions.get(req.params.session);
  if (client) {
    try { await client.logout(); } catch (error) { logger.warn({ err: error.message }, 'Logout session warning'); }
  }
  sessions.delete(req.params.session);
  states.set(req.params.session, 'DISCONNECTED');
  qrCodes.delete(req.params.session);
  await postWebhook('session.status', req.params.session, { status: 'DISCONNECTED' });
  res.json({ success: true, status: 'DISCONNECTED' });
});

const port = Number(process.env.PORT || 21465);
const host = process.env.HOST || '127.0.0.1';
app.listen(port, host, () => logger.info({ host, port }, 'WA gateway aktif'));
