            </section>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var toggleBtn = document.getElementById('adminSidebarToggle');
        if (!toggleBtn) {
            return;
        }

        var body = document.body;
        var icon = document.getElementById('adminSidebarToggleIcon');
        var storageKey = 'admin_sidebar_collapsed';

        function applyState(collapsed) {
            body.classList.toggle('admin-sidebar-collapsed', collapsed);
            if (icon) {
                icon.classList.remove('fa-angles-left', 'fa-angles-right');
                icon.classList.add(collapsed ? 'fa-angles-right' : 'fa-angles-left');
            }
            toggleBtn.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
            toggleBtn.setAttribute('title', collapsed ? 'Expand sidebar' : 'Minimize sidebar');
        }

        var saved = null;
        try {
            saved = localStorage.getItem(storageKey);
        } catch (e) {
            saved = null;
        }
        applyState(saved === '1');

        toggleBtn.addEventListener('click', function () {
            var next = !body.classList.contains('admin-sidebar-collapsed');
            applyState(next);
            try {
                localStorage.setItem(storageKey, next ? '1' : '0');
            } catch (e) {
                // Ignore storage failures; toggle still works in-memory.
            }
        });
    })();
</script>
</body>
</html>
