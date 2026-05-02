
</div><!-- fin container -->
<footer class="py-4 text-white mt-5">
    <div class="container">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <h6 class="fw-bold mb-1">
                    <i class="bi bi-droplet-fill text-danger"></i> BloodBridge
                </h6>
                <small class="text-white-50">
                    Plateforme intelligente de don de sang en Tunisie
                </small>
            </div>
            <div class="col-md-4 text-center">
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <?php
                    foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g)
                        echo "<span class='badge bg-danger'>{$g}</span>";
                    ?>
                </div>
                <small class="text-white-50 d-block mt-1">
                    Délai min entre dons : 56 jours
                </small>
            </div>
            <div class="col-md-4 text-md-end">
                <small class="text-white-50">
                    Projet PFA 2025/2026 — ESEN Tunis<br>
                    <strong class="text-white">
                        Khouloud BenRomdhane & Souli Youssef
                    </strong>
                </small>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ferme les alertes automatiquement après 4 secondes
    document.querySelectorAll('.alert').forEach(function(a) {
        setTimeout(function() {
            a.style.transition = 'opacity 0.5s';
            a.style.opacity = '0';
            setTimeout(function() { a.remove(); }, 500);
        }, 4000);
    });
});
</script>
</body>
</html>