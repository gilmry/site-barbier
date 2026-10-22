// Seul point d'accès réseau des îlots (invariant F1). Même origine ; JSON ; jeton CSRF de la session ;
// les erreurs problem+json deviennent des ApiError lisibles champ par champ.
import type { Transport } from '../generated/api';

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly problem: { title?: string; detail?: string; errors?: { field: string; message: string }[] },
    public readonly retryAfter: number | null,
  ) {
    super(problem.title ?? `Erreur ${status}`);
  }

  fieldErrors(): Record<string, string> {
    const out: Record<string, string> = {};
    for (const e of this.problem.errors ?? []) out[e.field.replace(/^body\./, '')] = e.message;
    return out;
  }
}

let csrfToken = '';
export function setCsrf(token: string | undefined): void {
  csrfToken = token ?? '';
}

export const transport: Transport = async (url, { method, body, form, query }) => {
  const qs = query ? new URLSearchParams(Object.entries(query).filter(([, v]) => v !== undefined) as [string, string][]).toString() : '';
  const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'barbier' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (csrfToken && method !== 'GET') headers['X-CSRF-Token'] = csrfToken;
  // FormData : le navigateur pose lui-même Content-Type multipart avec sa frontière.
  const res = await fetch(qs ? `${url}?${qs}` : url, { method, body: form ?? body, headers, credentials: 'same-origin' });
  if (res.status === 204) return undefined;
  const type = res.headers.get('Content-Type') ?? '';
  const data = type.includes('json') ? await res.json() : await res.text();
  if (!res.ok) throw new ApiError(res.status, typeof data === 'object' ? data : { title: String(data) }, Number(res.headers.get('Retry-After')) || null);
  return data;
};
