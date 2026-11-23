// Loaded on every page.

// Any form with a data-confirm="..." attribute asks for confirmation before submitting
// (used for delete buttons). One listener on the document handles all such forms.
document.addEventListener('submit', function (event) {
    const message = event.target.dataset.confirm;
    if (message && !confirm(message)) {
        event.preventDefault();
    }
});
