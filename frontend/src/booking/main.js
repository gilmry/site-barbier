import { mount } from 'svelte';
import Booking from './Booking.svelte';
import './booking.css';

const target = document.getElementById('booking');
if (target) {
  let strings = {};
  try {
    strings = JSON.parse(target.dataset.strings || '{}');
  } catch {
    strings = {};
  }
  const props = { lang: target.dataset.lang || 'fr', phone: target.dataset.phone || '', strings };
  target.textContent = '';
  mount(Booking, { target, props });
}
