// GÉNÉRÉ depuis openapi/site-barbier.openapi.json par frontend/scripts/gen-api.mjs.
// NE PAS ÉDITER À LA MAIN — régénéré au build ; le gate `contrat` vérifie sa fraîcheur.
/* eslint-disable */

export type Transport = (
  url: string,
  options: { method: string; body?: string; form?: FormData; query?: Record<string, string | undefined> },
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

export interface Translated {
  fr: string;
  nl?: string | null;
  en?: string | null;
}

export interface TranslatedOptional {
  fr?: string | null;
  nl?: string | null;
  en?: string | null;
}

export type Uuid = string;

export interface SectionField {
  label: string;
  max: number;
  rich: boolean;
  multiline: boolean;
  values: TranslatedOptional;
  missing: ("fr" | "nl" | "en")[];
}

export interface AdminSection {
  key: string;
  page: string;
  label: string;
  version: number;
  mediaId: Uuid | null;
  fields: Record<string, SectionField>;
}

export interface AdminSectionList {
  sections: AdminSection[];
}

export interface SectionUpdate {
  version: number;
  mediaId?: Uuid | null;
  fields: Record<string, TranslatedOptional>;
}

export interface Salon {
  name: string;
  phone: string;
  email: string;
  address: Translated;
  access: TranslatedOptional;
}

export interface Day {
  weekday: number;
  opens: string | null;
  closes: string | null;
}

export interface OpeningHours {
  days: Day[];
}

export interface AdminService {
  id: Uuid;
  categoryId: Uuid;
  name: TranslatedOptional;
  description: TranslatedOptional;
  priceCents: number;
  durationMin: number;
  published: boolean;
  position: number;
}

export interface AdminCategory {
  id: Uuid;
  position: number;
  name: TranslatedOptional;
  services: AdminService[];
}

export interface AdminCatalog {
  categories: AdminCategory[];
}

export interface CategoryInput {
  name: Translated;
  position?: number;
}

export interface ServiceInput {
  categoryId: Uuid;
  name: Translated;
  description: TranslatedOptional;
  priceCents: number;
  durationMin: number;
  published: boolean;
  position?: number;
}

export interface AdminBarber {
  id: Uuid;
  firstName: string;
  photoMediaId: Uuid | null;
  active: boolean;
  position: number;
  specialty: TranslatedOptional;
  bio: TranslatedOptional;
  serviceIds: Uuid[];
}

export interface AdminBarberList {
  barbers: AdminBarber[];
}

export interface BarberInput {
  firstName: string;
  specialty: Translated;
  bio: TranslatedOptional;
  photoMediaId?: Uuid | null;
  active: boolean;
  position?: number;
  serviceIds: Uuid[];
}

export interface Media {
  id: Uuid;
  src: string;
  width: number;
  height: number;
  alt: TranslatedOptional;
  warning: string | null;
}

export interface MediaList {
  media: Media[];
}

export interface MediaAlt {
  alt: TranslatedOptional;
}

export interface Created {
  id: Uuid;
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
    /** Sections éditables et leurs textes */
    listSections: (): Promise<AdminSectionList> =>
      transport(`/api/v1/admin/sections`, { method: 'GET' }) as Promise<AdminSectionList>,
    /** Modifier les textes d'une section (verrou optimiste) */
    updateSection: (key: string, input: SectionUpdate): Promise<AdminSection> =>
      transport(`/api/v1/admin/sections/${encodeURIComponent(key)}`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<AdminSection>,
    /** Coordonnées du salon */
    getSalon: (): Promise<Salon> =>
      transport(`/api/v1/admin/salon`, { method: 'GET' }) as Promise<Salon>,
    /** Modifier les coordonnées */
    updateSalon: (input: Salon): Promise<Salon> =>
      transport(`/api/v1/admin/salon`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<Salon>,
    /** Horaires du salon */
    getOpeningHours: (): Promise<OpeningHours> =>
      transport(`/api/v1/admin/opening-hours`, { method: 'GET' }) as Promise<OpeningHours>,
    /** Modifier les horaires */
    updateOpeningHours: (input: OpeningHours): Promise<OpeningHours> =>
      transport(`/api/v1/admin/opening-hours`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<OpeningHours>,
    /** Carte complète (y compris dépubliée) */
    getCatalog: (): Promise<AdminCatalog> =>
      transport(`/api/v1/admin/catalog`, { method: 'GET' }) as Promise<AdminCatalog>,
    /** Ajouter une catégorie */
    createCategory: (input: CategoryInput): Promise<Created> =>
      transport(`/api/v1/admin/categories`, { method: 'POST', body: JSON.stringify(input) }) as Promise<Created>,
    /** Modifier une catégorie */
    updateCategory: (id: string, input: CategoryInput): Promise<void> =>
      transport(`/api/v1/admin/categories/${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<void>,
    /** Supprimer une catégorie vide */
    deleteCategory: (id: string): Promise<void> =>
      transport(`/api/v1/admin/categories/${encodeURIComponent(id)}`, { method: 'DELETE' }) as Promise<void>,
    /** Ajouter une prestation */
    createService: (input: ServiceInput): Promise<Created> =>
      transport(`/api/v1/admin/services`, { method: 'POST', body: JSON.stringify(input) }) as Promise<Created>,
    /** Modifier une prestation */
    updateService: (id: string, input: ServiceInput): Promise<void> =>
      transport(`/api/v1/admin/services/${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<void>,
    /** Supprimer une prestation */
    deleteService: (id: string): Promise<void> =>
      transport(`/api/v1/admin/services/${encodeURIComponent(id)}`, { method: 'DELETE' }) as Promise<void>,
    /** Équipe (y compris inactifs) */
    listBarbers: (): Promise<AdminBarberList> =>
      transport(`/api/v1/admin/barbers`, { method: 'GET' }) as Promise<AdminBarberList>,
    /** Ajouter un barbier */
    createBarber: (input: BarberInput): Promise<Created> =>
      transport(`/api/v1/admin/barbers`, { method: 'POST', body: JSON.stringify(input) }) as Promise<Created>,
    /** Modifier un barbier */
    updateBarber: (id: string, input: BarberInput): Promise<void> =>
      transport(`/api/v1/admin/barbers/${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<void>,
    /** Images envoyées */
    listMedia: (): Promise<MediaList> =>
      transport(`/api/v1/admin/media`, { method: 'GET' }) as Promise<MediaList>,
    /** Envoyer une image (JPEG, PNG, WebP ; 8 Mo au plus) */
    uploadMedia: (form: FormData): Promise<Media> =>
      transport(`/api/v1/admin/media`, { method: 'POST', form }) as Promise<Media>,
    /** Texte alternatif d'une image */
    updateMedia: (id: string, input: MediaAlt): Promise<Media> =>
      transport(`/api/v1/admin/media/${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<Media>,
    /** Supprimer une image inutilisée */
    deleteMedia: (id: string): Promise<void> =>
      transport(`/api/v1/admin/media/${encodeURIComponent(id)}`, { method: 'DELETE' }) as Promise<void>,
  };
}

export type Client = ReturnType<typeof createClient>;
