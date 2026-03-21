<script src="../assets/js/jquery.min.js"></script>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <footer class="footer mt-auto py-3 bg-white border-top text-center">
        <div class="container">
            <span class="text-muted small">
                © 2026 Central Library Management System | 
                <strong>MIT Academy of Engineering, Alandi</strong> 
            </span>
            <div class="mt-1">
                <span class="badge badge-light border">PRN: 202301040137</span>
            </div>
        </div>
    </footer>

    <script src="../assets/js/jquery.min.js"></script>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // This ensures notifications disappear after 4 seconds automatically
        setTimeout(function() {
            $(".alert").fadeTo(500, 0).slideUp(500, function(){
                $(this).remove(); 
            });
        }, 4000);
    });
    </script>
</body>
</html>