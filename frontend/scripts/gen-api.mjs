// gen-api.mjs — génère le client TypeScript TYPÉ (src/generated/api.ts) depuis le contrat OpenAPI 3.1.
// Dérivé du kit Foyer, adapté à OpenAPI 3.1 (types en tableau, paramètres de chemin et de requête
// quelconques, PUT/PATCH/DELETE, 204). Les îlots ne connaissent d'URL que via ce client (anti-drift).
// NE PAS éditer api.ts à la main : le gate `contrat` le régénère et échoue s'il diffère.
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const SPEC = resolve(HERE, '../../openapi/site-barbier.openapi.json');
const OUT = process.env.API_TS_OUT || resolve(HERE, '../src/generated/api.ts');

const spec = JSON.parse(readFileSync(SPEC, 'utf8'));
const schemas = spec.components?.schemas ?? {};
const refName = (ref) => ref.split('/').pop();
const ident = (s) => s.replace(/[^A-Za-z0-9_]/g, '_');

function tsType(schema) {
  if (!schema) return 'unknown';
  if (schema.$ref) return refName(schema.$ref);
  if (schema.const !== undefined) return JSON.stringify(schema.const);
  if (schema.enum) return schema.enum.map((v) => JSON.stringify(v)).join(' | ');
  if (schema.oneOf || schema.anyOf) return (schema.oneOf ?? schema.anyOf).map(tsType).join(' | ');
  if (Array.isArray(schema.type)) {
    return schema.type.map((t) => (t === 'null' ? 'null' : tsType({ ...schema, type: t }))).join(' | ');
  }
  switch (schema.type) {
    case 'string': return 'string';
    case 'integer':
    case 'number': return 'number';
    case 'boolean': return 'boolean';
    case 'null': return 'null';
    case 'array': {
      const it = tsType(schema.items);
      return /[|&]/.test(it) ? `(${it})[]` : `${it}[]`;
    }
    case 'object':
    default: {
      if (schema.properties) {
        const req = new Set(schema.required ?? []);
        const fields = Object.entries(schema.properties)
          .map(([k, v]) => `  ${/^[A-Za-z_][A-Za-z0-9_]*$/.test(k) ? k : JSON.stringify(k)}${req.has(k) ? '' : '?'}: ${tsType(v)};`)
          .join('\n');
        return `{\n${fields}\n}`;
      }
      if (schema.additionalProperties && typeof schema.additionalProperties === 'object') {
        return `Record<string, ${tsType(schema.additionalProperties)}>`;
      }
      return 'Record<string, never>';
    }
  }
}

const types = Object.entries(schemas)
  .map(([name, s]) => (s.type === 'object' && s.properties ? `export interface ${name} ${tsType(s)}` : `export type ${name} = ${tsType(s)};`))
  .join('\n\n');

const success = (op) => {
  for (const code of ['200', '201', '204']) if (op.responses?.[code]) return [code, op.responses[code]];
  return [null, null];
};

const methods = [];
for (const [path, ops] of Object.entries(spec.paths ?? {})) {
  for (const http of ['get', 'post', 'put', 'patch', 'delete']) {
    const op = ops[http];
    if (!op) continue;
    if (!op.operationId) throw new Error(`operationId manquant : ${http.toUpperCase()} ${path}`);
    const pathParams = (op.parameters ?? []).filter((p) => p.in === 'path');
    const queryParams = (op.parameters ?? []).filter((p) => p.in === 'query');
    const body = op.requestBody?.content?.['application/json']?.schema;
    const multipart = !!op.requestBody?.content?.['multipart/form-data'];
    const [code, resp] = success(op);
    const ret = code === '204' || !resp?.content ? 'void' : tsType(resp.content['application/json']?.schema);
    const args = [
      ...pathParams.map((p) => `${ident(p.name)}: string`),
      body ? `input: ${tsType(body)}` : null,
      multipart ? 'form: FormData' : null,
      queryParams.length
        ? `query${queryParams.some((p) => p.required) ? '' : '?'}: { ${queryParams.map((p) => `${JSON.stringify(p.name)}${p.required ? '' : '?'}: ${tsType(p.schema)}`).join('; ')} }`
        : null,
    ].filter(Boolean).join(', ');
    const url = path.replace(/\{([^}]+)\}/g, (_, n) => `\${encodeURIComponent(${ident(n)})}`);
    const opts = [`method: '${http.toUpperCase()}'`, body ? 'body: JSON.stringify(input)' : null, multipart ? 'form' : null, queryParams.length ? 'query' : null].filter(Boolean).join(', ');
    methods.push(`    /** ${op.summary ?? ''} */\n    ${op.operationId}: (${args}): Promise<${ret}> =>\n      transport(\`${url}\`, { ${opts} }) as Promise<${ret}>,`);
  }
}

const out = `// GÉNÉRÉ depuis openapi/site-barbier.openapi.json par frontend/scripts/gen-api.mjs.
// NE PAS ÉDITER À LA MAIN — régénéré au build ; le gate \`contrat\` vérifie sa fraîcheur.
/* eslint-disable */

export type Transport = (
  url: string,
  options: { method: string; body?: string; form?: FormData; query?: Record<string, string | undefined> },
) => Promise<unknown>;

${types}

export function createClient(transport: Transport) {
  return {
${methods.join('\n')}
  };
}

export type Client = ReturnType<typeof createClient>;
`;

mkdirSync(dirname(OUT), { recursive: true });
writeFileSync(OUT, out);
console.log(`api.ts généré : ${OUT}`);
