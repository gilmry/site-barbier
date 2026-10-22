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

export interface AdminSession {
  stage: "none" | "pre_enrolment" | "password_ok" | "full" | "recovery";
  email?: string;
  csrf?: string;
  recentTotp?: boolean;
  recoveryCodesRemaining?: number;
}

export interface LoginInput {
  email: string;
  password: string;
}

export interface CodeInput {
  code: string;
}

export interface TotpEnrollment {
  secret: string;
  uri: string;
}

export interface RecoveryCodeList {
  codes: string[];
}

export function createClient(transport: Transport) {
  return {
    /** État du service et de la base */
    getHealth: (): Promise<Health> =>
      transport(`/api/v1/health`, { method: 'GET' }) as Promise<Health>,
    /** Étape de connexion courante (stage « none » sans session) */
    getAdminSession: (): Promise<AdminSession> =>
      transport(`/api/v1/admin/session`, { method: 'GET' }) as Promise<AdminSession>,
    /** Étape 1 : e-mail et mot de passe */
    login: (input: LoginInput): Promise<AdminSession> =>
      transport(`/api/v1/admin/session`, { method: 'POST', body: JSON.stringify(input) }) as Promise<AdminSession>,
    /** Déconnexion */
    logout: (): Promise<void> =>
      transport(`/api/v1/admin/session`, { method: 'DELETE' }) as Promise<void>,
    /** Étape 2 : code de l'appli (ou TOTP récent) */
    verifyTotp: (input: CodeInput): Promise<AdminSession> =>
      transport(`/api/v1/admin/session/totp`, { method: 'POST', body: JSON.stringify(input) }) as Promise<AdminSession>,
    /** Connexion par code de secours (session « secours ») */
    useRecoveryCode: (input: CodeInput): Promise<AdminSession> =>
      transport(`/api/v1/admin/session/recovery`, { method: 'POST', body: JSON.stringify(input) }) as Promise<AdminSession>,
    /** (Ré)activer le double facteur : nouveau secret */
    startTotpEnrollment: (): Promise<TotpEnrollment> =>
      transport(`/api/v1/admin/account/totp`, { method: 'POST' }) as Promise<TotpEnrollment>,
    /** Confirmer avec un premier code → codes de secours */
    confirmTotpEnrollment: (input: CodeInput): Promise<RecoveryCodeList> =>
      transport(`/api/v1/admin/account/totp/confirm`, { method: 'POST', body: JSON.stringify(input) }) as Promise<RecoveryCodeList>,
    /** Régénérer les codes de secours (TOTP récent) */
    regenerateRecoveryCodes: (): Promise<RecoveryCodeList> =>
      transport(`/api/v1/admin/account/recovery-codes`, { method: 'POST' }) as Promise<RecoveryCodeList>,
  };
}

export type Client = ReturnType<typeof createClient>;
