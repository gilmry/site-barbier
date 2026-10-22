# Parcours de référence (source de vérité partagée)

Chaque fichier `*.journey.mjs` décrit **un** parcours, en langage métier, rejoué par les **trois** harnais
(`harness/browser/run.mjs`) : `e2e` (à la vitesse, bloquant), `visual` (goldens, bloquant),
`demo` (une action par seconde, captures + vidéo, vitrine HTML, non bloquant). On ne recopie jamais un
parcours dans un test ou dans une doc : on l'écrit ici, une fois (invariant J1 du gate `verify`).

Forme : `export default { id, title, lang, steps: [{ id, description, action(page, ctx), check(page, ctx), snapshot? }] }`.
`snapshot: 'nom'` désigne un golden visuel capturé après l'étape.
