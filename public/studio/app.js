const menu = document.querySelector('.menu-toggle');
const nav = document.querySelector('.main-nav');

menu?.addEventListener('click', () => {
  const open = nav.classList.toggle('open');
  menu.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('.main-nav a').forEach((link) => link.addEventListener('click', () => {
  nav.classList.remove('open');
  menu?.setAttribute('aria-expanded', 'false');
}));

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => { if (entry.isIntersecting) entry.target.classList.add('is-visible'); });
}, { threshold: 0.14 });
document.querySelectorAll('.reveal').forEach((item) => observer.observe(item));

const toggles = document.querySelectorAll('[data-billing]');
toggles.forEach((toggle) => toggle.addEventListener('click', () => {
  const billing = toggle.dataset.billing;
  toggles.forEach((button) => button.classList.toggle('active', button === toggle));
  document.querySelectorAll('[data-monthly]').forEach((price) => { price.textContent = price.dataset[billing]; });
}));

document.getElementById('year').textContent = new Date().getFullYear();
