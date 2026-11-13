(() => {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const page = () => document.querySelector('.page-fade');
  let leaving = false;

  function fadeOutThen(callback) {
    const element = page();

    if (!element || reduceMotion.matches) {
      callback();
      return;
    }

    element.classList.add('is-leaving');
    window.setTimeout(callback, 180);
  }

  document.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    const link = event.target.closest('a');
    if (!link) return;
    if (link.target && link.target !== '_self') return;
    if (link.hasAttribute('download') || link.dataset.noTransition !== undefined) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) return;

    const samePath = url.pathname === window.location.pathname && url.search === window.location.search;
    if (samePath) return;

    event.preventDefault();
    if (leaving) return;

    leaving = true;
    fadeOutThen(() => {
      window.location.href = url.href;
    });
  });

  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (event.defaultPrevented || (form.target && form.target !== '_self')) return;
    if (form.dataset.noTransition !== undefined || form.method.toLowerCase() === 'dialog') return;

    event.preventDefault();
    if (leaving) return;

    leaving = true;
    fadeOutThen(() => {
      form.submit();
    });
  });

  window.addEventListener('pageshow', () => {
    leaving = false;
    document.querySelectorAll('.page-fade.is-leaving').forEach(element => element.classList.remove('is-leaving'));
  });
})();
