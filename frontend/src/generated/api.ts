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

export interface PublicService {
  id: Uuid;
  category: string;
  name: string;
  description: string;
  priceCents: number;
  price: string;
  durationMin: number;
}

export interface PublicServiceList {
  services: PublicService[];
}

export interface PublicBarber {
  id: Uuid;
  firstName: string;
  specialty: string;
  photo: string | null;
}

export interface PublicBarberList {
  barbers: PublicBarber[];
}

export interface DayList {
  days: string[];
}

export interface Slot {
  start: string;
  time: string;
}

export interface SlotList {
  slots: Slot[];
}

export interface BookingInput {
  serviceId: Uuid;
  barberId: string;
  start: string;
  name: string;
  phone: string;
  email: string;
  lang: "fr" | "nl" | "en";
  website?: string;
}

export interface BookingSummary {
  id: Uuid;
  service: string;
  barber: string;
  start: string;
  date: string;
  time: string;
  durationMin: number;
  price: string;
  state?: "confirmed" | "cancelled";
}

export interface CancelInput {
  token: string;
}

export interface AvailabilityRange {
  weekday: number;
  starts: string;
  ends: string;
}

export interface Availability {
  ranges: AvailabilityRange[];
}

export interface Absence {
  id: Uuid;
  barberId: Uuid | null;
  barberName: string | null;
  startsAt: string;
  endsAt: string;
  startsLocal: string;
  endsLocal: string;
}

export interface AbsenceList {
  absences: Absence[];
}

export interface AbsenceInput {
  barberId: Uuid | null;
  startsAt: string;
  endsAt: string;
}

export interface AdminBooking {
  id: Uuid;
  barberId: Uuid;
  barberName: string;
  serviceName: string;
  time: string;
  endTime: string;
  durationMin: number;
  priceCents: number;
  state: "confirmed" | "cancelled";
  lang: string;
  clientName: string;
  clientPhone: string;
  clientEmail: string;
  cancelledBy: string | null;
}

export interface AdminBookingList {
  bookings: AdminBooking[];
}

export interface SalonCancelInput {
  message?: string;
}

export interface MailEntry {
  id: Uuid;
  bookingId: Uuid | null;
  kind: string;
  recipient: string;
  state: "sent" | "failed";
  error: string | null;
  attempts: number;
  createdAt: string;
  sentAt: string | null;
}

export interface MailLogView {
  entries: MailEntry[];
  failed: number;
}

export interface RetryInput {
  ids: Uuid[];
}

export interface RetryResult {
  results: ({
  id: Uuid;
  state: "sent" | "failed";
})[];
}

export interface EmailInput {
  email: string;
}

export interface EraseInput {
  email: string;
  confirmWithoutEmail: boolean;
}

export interface ClientBooking {
  id: Uuid;
  barberName: string;
  startsAt: string;
  state: string;
  clientName: string;
  clientPhone: string;
  clientEmail: string;
}

export interface ClientBookingList {
  bookings: ClientBooking[];
}

export interface ClientExport {
  email: string;
  exportedAt: string;
  bookings: {
  service: string;
  barber: string;
  startsAt: string;
  state: string;
  name: string;
  phone: string;
  email: string;
  lang: string;
  createdAt: string;
}[];
}

export interface EraseResult {
  erased: number;
  notified: number;
  notifyFailed: number;
}

export interface AdminHealth {
  version: string;
  php: string;
  phpSecurityEnd: string | null;
  phpSupport: "ok" | "ending" | "ended" | "unknown";
  pendingMigrations: string[];
  failedMails: number;
  lastPurge: string | null;
  lastBackup: string | null;
  backupDue: boolean;
  uploadsBytes: number;
  logWritable: boolean;
  smtpConfigured: boolean;
}

export interface Migrations {
  applied: string[];
  pending: string[];
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
    /** Prestations réservables */
    listBookableServices: (query: { "lang": "fr" | "nl" | "en" }): Promise<PublicServiceList> =>
      transport(`/api/v1/services`, { method: 'GET', query }) as Promise<PublicServiceList>,
    /** Barbiers qui réalisent la prestation */
    listBookableBarbers: (query: { "service": string; "lang": "fr" | "nl" | "en" }): Promise<PublicBarberList> =>
      transport(`/api/v1/barbers`, { method: 'GET', query }) as Promise<PublicBarberList>,
    /** Jours ayant au moins un créneau libre */
    listBookableDays: (query: { "service": string; "barber": string; "from": string; "to": string }): Promise<DayList> =>
      transport(`/api/v1/availability/days`, { method: 'GET', query }) as Promise<DayList>,
    /** Créneaux libres d'un jour */
    listFreeSlots: (query: { "service": string; "barber": string; "date": string }): Promise<SlotList> =>
      transport(`/api/v1/availability/slots`, { method: 'GET', query }) as Promise<SlotList>,
    /** Réserver un créneau */
    bookSlot: (input: BookingInput): Promise<BookingSummary> =>
      transport(`/api/v1/bookings`, { method: 'POST', body: JSON.stringify(input) }) as Promise<BookingSummary>,
    /** Annuler par le lien reçu par e-mail */
    cancelBooking: (input: CancelInput): Promise<BookingSummary> =>
      transport(`/api/v1/bookings/cancel`, { method: 'POST', body: JSON.stringify(input) }) as Promise<BookingSummary>,
    /** Disponibilités d'un barbier */
    getAvailability: (id: string): Promise<Availability> =>
      transport(`/api/v1/admin/barbers/${encodeURIComponent(id)}/availability`, { method: 'GET' }) as Promise<Availability>,
    /** Modifier les disponibilités */
    setAvailability: (id: string, input: Availability): Promise<Availability> =>
      transport(`/api/v1/admin/barbers/${encodeURIComponent(id)}/availability`, { method: 'PUT', body: JSON.stringify(input) }) as Promise<Availability>,
    /** Absences et fermetures à venir */
    listAbsences: (): Promise<AbsenceList> =>
      transport(`/api/v1/admin/absences`, { method: 'GET' }) as Promise<AbsenceList>,
    /** Absence d'un barbier ou fermeture du salon (barberId null) */
    addAbsence: (input: AbsenceInput): Promise<Created> =>
      transport(`/api/v1/admin/absences`, { method: 'POST', body: JSON.stringify(input) }) as Promise<Created>,
    /** Retirer une absence */
    deleteAbsence: (id: string): Promise<void> =>
      transport(`/api/v1/admin/absences/${encodeURIComponent(id)}`, { method: 'DELETE' }) as Promise<void>,
    /** Planning d'un jour */
    listBookings: (query: { "date": string; "barber"?: string; "state"?: "confirmed" | "cancelled" }): Promise<AdminBookingList> =>
      transport(`/api/v1/admin/bookings`, { method: 'GET', query }) as Promise<AdminBookingList>,
    /** Annuler une réservation (le client est prévenu) */
    cancelBookingBySalon: (id: string, input: SalonCancelInput): Promise<void> =>
      transport(`/api/v1/admin/bookings/${encodeURIComponent(id)}/cancel`, { method: 'POST', body: JSON.stringify(input) }) as Promise<void>,
    /** Journal des e-mails */
    getMailLog: (): Promise<MailLogView> =>
      transport(`/api/v1/admin/mail-log`, { method: 'GET' }) as Promise<MailLogView>,
    /** Relancer des envois */
    retryMails: (input: RetryInput): Promise<RetryResult> =>
      transport(`/api/v1/admin/mail-log/retry`, { method: 'POST', body: JSON.stringify(input) }) as Promise<RetryResult>,
    /** Réservations d'une adresse e-mail (FR-012) */
    privacySearch: (input: EmailInput): Promise<ClientBookingList> =>
      transport(`/api/v1/admin/privacy/search`, { method: 'POST', body: JSON.stringify(input) }) as Promise<ClientBookingList>,
    /** Exporter les données d'une adresse */
    privacyExport: (input: EmailInput): Promise<ClientExport> =>
      transport(`/api/v1/admin/privacy/export`, { method: 'POST', body: JSON.stringify(input) }) as Promise<ClientExport>,
    /** Effacer les données d'une adresse */
    privacyErase: (input: EraseInput): Promise<EraseResult> =>
      transport(`/api/v1/admin/privacy/erase`, { method: 'POST', body: JSON.stringify(input) }) as Promise<EraseResult>,
    /** Santé du site et rappels (tableau de bord) */
    getAdminHealth: (): Promise<AdminHealth> =>
      transport(`/api/v1/admin/health`, { method: 'GET' }) as Promise<AdminHealth>,
    /** Sauvegarde SQL complète (TOTP récent exigé) */
    downloadBackup: (): Promise<unknown> =>
      transport(`/api/v1/admin/backup`, { method: 'GET' }) as Promise<unknown>,
    /** Migrations appliquées et en attente */
    getMigrations: (): Promise<Migrations> =>
      transport(`/api/v1/admin/migrations`, { method: 'GET' }) as Promise<Migrations>,
    /** Appliquer les migrations en attente (TOTP récent exigé) */
    applyMigrations: (): Promise<Migrations> =>
      transport(`/api/v1/admin/migrations`, { method: 'POST' }) as Promise<Migrations>,
  };
}

export type Client = ReturnType<typeof createClient>;
