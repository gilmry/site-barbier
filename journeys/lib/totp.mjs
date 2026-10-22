// TOTP RFC 6238 (SHA1, 30 s, 6 chiffres) pour les parcours : joue le rôle de l'appli d'authentification.
import { createHmac } from 'node:crypto';

function base32Decode(s) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const c of s.replace(/=+$/, '')) bits += alphabet.indexOf(c).toString(2).padStart(5, '0');
  const bytes = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
  return Buffer.from(bytes);
}

/** Code courant, en évitant le pas déjà consommé (anti-rejeu côté serveur) : attend le pas suivant si besoin. */
export async function totpCode(secretBase32, state = {}) {
  let step = Math.floor(Date.now() / 1000 / 30);
  if (state.lastStep !== undefined && step <= state.lastStep) {
    await new Promise((r) => setTimeout(r, (30 - ((Date.now() / 1000) % 30)) * 1000 + 300));
    step = Math.floor(Date.now() / 1000 / 30);
  }
  state.lastStep = step;
  const msg = Buffer.alloc(8);
  msg.writeBigUInt64BE(BigInt(step));
  const h = createHmac('sha1', base32Decode(secretBase32)).update(msg).digest();
  const o = h[19] & 0x0f;
  const bin = ((h[o] & 0x7f) << 24) | (h[o + 1] << 16) | (h[o + 2] << 8) | h[o + 3];
  return String(bin % 1_000_000).padStart(6, '0');
}
