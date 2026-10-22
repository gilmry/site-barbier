<script>
  // Coquille de l'administration : navigation par rubriques. Chaque story à écran y ajoute sa rubrique.
  import { api } from '../shared/api';
  import Dashboard from './views/Dashboard.svelte';
  import Account from './views/Account.svelte';
  let { session, onchange, oncodes } = $props();

  const sections = [
    { id: 'dashboard', label: 'Tableau de bord', component: Dashboard },
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
