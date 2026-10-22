// Client typé unique des îlots : généré depuis la spec (src/generated/api.ts) + transport commun.
import { createClient } from '../generated/api';
import { transport } from './transport';

export const api = createClient(transport);
export { ApiError, setCsrf } from './transport';
