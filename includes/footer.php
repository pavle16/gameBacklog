</main>
<footer class="site-footer">
    <p>GameBacklog &copy; <?= date('Y') ?></p>
</footer>
<script src="<?= url('public/js/app.js') ?>"></script>
<?php foreach ($pageScripts ?? [] as $script): ?>
    <script src="<?= url($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
