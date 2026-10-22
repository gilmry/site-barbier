<script>
  // Coquille de l'administration : navigation par rubriques. Chaque story à écran y ajoute sa rubrique.
  import { api } from '../shared/api';
  import Dashboard from './views/Dashboard.svelte';
  import Account from './views/Account.svelte';
  import Contenu from './views/Contenu.svelte';
  import Carte from './views/Carte.svelte';
  import Equipe from './views/Equipe.svelte';
  import Horaires from './views/Horaires.svelte';
  import Images from './views/Images.svelte';
  let { session, onchange, oncodes } = $props();

  const sections = [
    { id: 'dashboard', label: 'Tableau de bord', component: Dashboard },
    { id: 'contenu', label: 'Contenu du site', component: Contenu },
    { id: 'carte', label: 'La carte', component: Carte },
    { id: 'equipe', label: 'L’équipe', component: Equipe },
    { id: 'horaires', label: 'Horaires et coordonnées', component: Horaires },
    { id: 'images', label: 'Images', component: Images },
    { id: 'account', label: 'Mon compte', component: Account },
  ];
  let current = $state(sections.find((s) => s.id === location.hash.slice(1))?.id ?? 'dashboard');
  const active = $derived(sections.find((s) => s.id === current) ?? sections[0]);

  function go(id) {
    current = id;
    history.replaceState(null, '', `#${id}`);
  }

  async function logout() {
    await api.logout().catch(() => {});
    onchange();
  }
</script>

<div class="shell">
  <nav aria-label="Rubriques">
    <strong>Administration</strong>
    <ul>
      {#each sections as s}
        <li><button aria-current={current === s.id ? 'page' : undefined} onclick={() => go(s.id)}>{s.label}</button></li>
      {/each}
    </ul>
    <p class="hint">{session.email}</p>
    <button class="ghost" onclick={logout}>Se déconnecter</button>
  </nav>
  <main>
    {#key current}
      <active.component {session} {onchange} {oncodes} />
    {/key}
  </main>
</div>
