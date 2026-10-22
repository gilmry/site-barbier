// Message lisible pour une erreur d'API (problem+json) ; erreurs par champ pour les formulaires.
import { ApiError } from '../../shared/api';

export function messageOf(e, fallback = 'L’enregistrement a échoué.') {
  if (e instanceof ApiError) return e.problem.title ?? fallback;
  return fallback;
}

export function fieldsOf(e) {
  return e instanceof ApiError ? e.fieldErrors() : {};
}
