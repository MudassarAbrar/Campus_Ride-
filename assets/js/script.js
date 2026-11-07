document.addEventListener('click', function (event) {
    if (!event.target.matches('[data-toggle-target]')) {
        return;
    }

    var targetId = event.target.getAttribute('data-toggle-target');
    var input = document.getElementById(targetId);

    if (!input) {
        return;
    }

    input.type = input.type === 'password' ? 'text' : 'password';
});
