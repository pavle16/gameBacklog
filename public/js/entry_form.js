// UX only: show just the fields that make sense for the selected status.
// Hidden fields are also disabled, so the browser doesn't send them at all.
// The server validates everything again – this script can be turned off or bypassed.
(function () {
    const status = document.getElementById('status');
    if (!status) return;

    // Show or hide a field (the <label> wrapper) and enable/disable its input
    function setVisible(id, visible) {
        const field = document.getElementById(id);
        field.hidden = !visible;
        field.querySelectorAll('input, select').forEach(function (input) {
            input.disabled = !visible;
        });
    }

    function update() {
        const s = status.value;
        setVisible('field-rating',   s !== 'backlog');
        setVisible('field-started',  s !== 'backlog');
        setVisible('field-hours',    s !== 'backlog');
        setVisible('field-finished', s === 'completed');
        document.getElementById('finished_at').required = (s === 'completed');
    }

    status.addEventListener('change', update);
    update();   // run once on page load (also after a failed submit)
})();
