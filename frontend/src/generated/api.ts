// GÉNÉRÉ depuis openapi/site-barbier.openapi.json par frontend/scripts/gen-api.mjs.
// NE PAS ÉDITER À LA MAIN — régénéré au build ; le gate `contrat` vérifie sa fraîcheur.
/* eslint-disable */

export type Transport = (
  url: string,
  options: { method: string; body?: string; query?: Record<string, string | undefined> },
) => Promise<unknown>;

export interface Health {
  status: "ok" | "degraded";
  database: "ok" | "unreachable";
}

export interface Problem {
  type: string;
  title: string;
  status: number;
  detail?: string;
  errors?: {
  field: string;
  message: string;
}[];
}

export function createClient(transport: Transport) {
  return {
    /** État du service et de la base */
    getHealth: (): Promise<Health> =>
      transport(`/api/v1/health`, { method: 'GET' }) as Promise<Health>,
  };
}

export type Client = ReturnType<typeof createClient>;
