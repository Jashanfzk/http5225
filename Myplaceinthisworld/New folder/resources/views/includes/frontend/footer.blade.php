<footer id="footer" class="footer position-relative dark-background pt-2">

  <div class="footer-texture mt-2">
    <div class="textured-img"></div>
  </div>
    <div class="container footer-top">
      <div class="row gy-4 align-items-center">
        <div class="col-lg-2 col-md-12 footer-about">
          <a href="{{ url('/') }}" class="logo d-flex align-items-center">
            <img src="assets/img/logo.png" alt="My Place In This World">
          </a>
        </div>

        <div class="col-lg-8 col-md-12 footer-links">
          
          <div class="d-flex justify-content-center align-items-center centered-content">
            <div class="content-wrapper text-center px-3">
              <div class="highlight-text">CELEBRATION</div>
              <div class="dot-wrapper"><span class="dot"></span></div>
              <div class="highlight-text">TRUTH</div>
              <div class="dot-wrapper"><span class="dot"></span></div>
              <div class="highlight-text">EMPOWERMENT</div>
            </div>
          </div>
        
        </div>

        <div class="col-lg-2 col-md-12 footer-links">
          <div>
            <h3 class="mb-2">Follow us:</h3>
            <div class="social-links d-flex mt-2">
              <a href=""><i class="bi bi-linkedin"></i></a>
              <a href=""><i class="bi bi-youtube"></i></a>
            </div>
          </div>
  
          <a href="#" class="btn btn-cta btn-danger mt-3">Contact Us</a>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <strong class="px-1 sitename">2021 MyPlaceinthisWorld</strong> <span>All Rights Reserved.</span></p>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>


<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script>
<script src="{{ asset('assets/vendor/aos/aos.js') }}"></script>
<script src="{{ asset('assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/purecounter/purecounter_vanilla.js') }}"></script>
<script src="{{ asset('assets/vendor/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
<script src="{{ asset('assets/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
<script src="{{ asset('assets/vendor/glightbox/js/glightbox.min.js') }}"></script>

<!-- Main JS File -->
<script src="{{ asset('assets/js/main.js') }}"></script>

@stack('scripts') {{-- Optional: for page-specific scripts --}}
