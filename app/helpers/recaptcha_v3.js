(function () {
    const siteKey = window.cdrRecaptchaSiteKey;
    const actions = {'/reserve.php': 'reserve', '/contact.php': 'contact', '/subscribe.php': 'subscribe'};
    const pending = new WeakSet();
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const target = new URL(form.action, window.location.href);
        const action = target.origin === window.location.origin ? actions[target.pathname] : null;
        if (!action || form.method.toLowerCase() !== 'post') return;
        event.preventDefault();
        event.stopImmediatePropagation();
        if (pending.has(form) || !form.reportValidity()) return;
        pending.add(form);
        let message = form.querySelector('.captcha-feedback');
        if (!message) {
            message = document.createElement('p');
            message.className = 'captcha-feedback col-12 text-warning';
            message.setAttribute('role', 'status');
            form.appendChild(message);
        }
        message.textContent = 'A enviar…';
        const buttons = Array.from(form.querySelectorAll('button, input[type="submit"]'));
        const states = buttons.map(function (button) { return button.disabled; });
        buttons.forEach(function (button) { button.disabled = true; });
        let timer;
        const timeout = new Promise(function (_, reject) {
            timer = setTimeout(function () { reject(new Error('timeout')); }, 10000);
        });
        const verification = new Promise(function (resolve, reject) {
            if (!window.grecaptcha || !siteKey) { reject(new Error('unavailable')); return; }
            window.grecaptcha.ready(function () {
                try { Promise.resolve(window.grecaptcha.execute(siteKey, {action: action})).then(resolve, reject); }
                catch (error) { reject(error); }
            });
        });
        Promise.race([verification, timeout]).then(function (token) {
            clearTimeout(timer);
            if (typeof token !== 'string' || !token) throw new Error('empty-token');
            let input = form.querySelector('input[name="g-recaptcha-response"]');
            if (!input) {
                input = document.createElement('input'); input.type = 'hidden'; input.name = 'g-recaptcha-response'; form.appendChild(input);
            }
            input.value = token;
            HTMLFormElement.prototype.submit.call(form);
        }).catch(function () {
            clearTimeout(timer);
            pending.delete(form);
            buttons.forEach(function (button, index) { button.disabled = states[index]; });
            message.textContent = 'Não foi possível enviar. Verifica a ligação e tenta novamente.';
        });
    }, true);
}());
