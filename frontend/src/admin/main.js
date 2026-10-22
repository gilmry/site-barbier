import { mount } from 'svelte';
import App from './App.svelte';
import './admin.css';

const target = document.getElementById('admin');
if (target) {
  target.textContent = '';
  mount(App, { target });
}
