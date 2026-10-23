const revealEls = document.querySelectorAll('.reveal');
const io = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('in');
      io.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });
revealEls.forEach(el => io.observe(el));

document.querySelectorAll('.faq-item').forEach(item => {
  const q = item.querySelector('.faq-q');
  const a = item.querySelector('.faq-a');
  q.addEventListener('click', () => {
    const isActive = item.classList.contains('active');
    document.querySelectorAll('.faq-item').forEach(i => {
      i.classList.remove('active');
      i.querySelector('.faq-a').style.maxHeight = null;
    });
    if (!isActive) {
      item.classList.add('active');
      a.style.maxHeight = a.scrollHeight + 'px';
    }
  });
});

const topBtn = document.getElementById('topBtn');
window.addEventListener('scroll', () => {
  topBtn.classList.toggle('show', window.scrollY > 600);
});
topBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

const header = document.querySelector('header');
window.addEventListener('scroll', () => {
  header.style.padding = window.scrollY > 40
    ? '0.7rem clamp(1.2rem, 4vw, 4rem)'
    : '1.1rem clamp(1.2rem, 4vw, 4rem)';
});
