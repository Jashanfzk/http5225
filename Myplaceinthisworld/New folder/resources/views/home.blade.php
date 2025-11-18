@extends('layouts.app')

@section('title', 'Home')

@section('content')

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">

      <div class="hero-wrapper">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-lg-6 hero-content" data-aos="fade-right" data-aos-delay="100">
              <h1>Inspiring Excellence Through Education</h1>
              <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas eget lacus id tortor facilisis tincidunt. Donec gravida risus at sollicitudin luctus.</p>
              <div class="stats-row">
                <div class="stat-item">
                  <span class="stat-number">96%</span>
                  <span class="stat-label">Employment Rate</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">12:1</span>
                  <span class="stat-label">Student-Teacher Ratio</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">50+</span>
                  <span class="stat-label">Programs</span>
                </div>
              </div>
              <div class="action-buttons">
                <a href="#" class="btn-primary">Start Your Journey</a>
                <a href="#" class="btn-secondary">Virtual Tour</a>
              </div>
            </div>
            <div class="col-lg-6 hero-media" data-aos="zoom-in" data-aos-delay="200">
              <img src="assets/img/education/showcase-6.webp" alt="Education" class="img-fluid main-image">
              <div class="image-overlay">
                <div class="badge-accredited">
                  <i class="bi bi-patch-check-fill"></i>
                  <span>Accredited Excellence</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="feature-cards-wrapper featured-by bg-dark" data-aos="fade-up" data-aos-delay="300">
        <div class="container">
          <div class="row gy-4">
            <h2 class="text-white pt-2">As featured by:</h2>
            <div class="col-lg-3" data-aos="fade-up" data-aos-delay="100">
              <div class="feature-card align-items-center">
                <a href="https://www.cbc.ca/news/canada/kitchener-waterloo/guelph-black-heritage-society-lorraine-harris-curriculum-1.5906779" class="text-center" target="_blank">
                <img src="assets/img/CBC-logo.png" class="img-fluid">
                </a>
              </div>
            </div>
            <div class="col-lg-3" data-aos="fade-up" data-aos-delay="100">
              <div class="feature-card align-items-center">
                <a href="https://www.ctvnews.ca/kitchener/article/black-heritage-curriculum-set-to-roll-out-in-waterloo-region-catholic-schools/" class="text-center" target="_blank">
                <img src="assets/img/CTV-logo.png" class="img-fluid">
                </a>
              </div>
            </div>
            <div class="col-lg-3" data-aos="fade-up" data-aos-delay="100">
              <div class="feature-card align-items-center">
                <a href="javascript:;" class="text-center">
                <img src="assets/img/ontario-logo.png" class="img-fluid">
                </a>
              </div>
            </div>
            <div class="col-lg-3" data-aos="fade-up" data-aos-delay="100">
              <div class="feature-card align-items-center">
                <a href="https://blogs1.conestogac.on.ca/news/2024/03/capstone_project_creates_marke.php" class="text-center" target="_blank">
                <img src="assets/img/conestoga-logo.svg" class="img-fluid">
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- <div class="upcoming-event" data-aos="fade-up" data-aos-delay="400">
        <div class="container">
          <div class="event-content">
            <div class="event-date">
              <span class="day">15</span>
              <span class="month">NOV</span>
            </div>
            <div class="event-info">
              <h3>Spring Semester Open House</h3>
              <p>Join us to explore campus facilities, meet our faculty, and learn about scholarship opportunities.</p>
            </div>
            <div class="event-action">
              <a href="#" class="btn-event">RSVP Now</a>
              <span class="countdown">Starts in 3 weeks</span>
            </div>
          </div>
        </div>
      </div> -->

    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <div class="about-content" data-aos="fade-up" data-aos-delay="200">
              <img src="assets/img/education/campus-5.webp" alt="Campus" class="img-fluid rounded">
            </div>
          </div>

          <div class="col-lg-6">
            <div class="about-content" data-aos="fade-up" data-aos-delay="200">
              <h3>Our Story</h3>
              <h2>Educating Minds, Inspiring Hearts</h2>
              <p>As members of the BIPOC (Black, Indigenous, and People of Colour) community, our goal is to provide rich and relevant resources that promote equity, diversity, and inclusion. This work supports students and helps build a foundation for the best outcomes.</p>
            </div>
            <div class="about-image" data-aos="zoom-in" data-aos-delay="300">

              <div class="mission-vision" data-aos="fade-up" data-aos-delay="400">
                <div class="mission">
                  <h3>Our Mission</h3>
                  <p>We create inclusive educational resources that reflect the voices and experiences of BIPOC communities. Our goal is to support student growth and promote equity in classrooms and beyond.</p>
                </div>

                <div class="vision">
                  <h3>Our Vision</h3>
                  <p>We envision schools where every student feels seen and supported. Through collaboration and advocacy, we aim to create lasting change driven by diversity and community leadership.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- <div class="row mt-5">
          <div class="col-lg-12">
            <div class="core-values" data-aos="fade-up" data-aos-delay="500">
              <h3 class="text-center mb-4">Core Values</h3>
              <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <div class="col">
                  <div class="value-card">
                    <div class="value-icon">
                      <i class="bi bi-book"></i>
                    </div>
                    <h4>Academic Excellence</h4>
                    <p>Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.</p>
                  </div>
                </div>

                <div class="col">
                  <div class="value-card">
                    <div class="value-icon">
                      <i class="bi bi-people"></i>
                    </div>
                    <h4>Community Engagement</h4>
                    <p>Vivamus magna justo, lacinia eget consectetur sed, convallis at tellus.</p>
                  </div>
                </div>

                <div class="col">
                  <div class="value-card">
                    <div class="value-icon">
                      <i class="bi bi-lightbulb"></i>
                    </div>
                    <h4>Innovation</h4>
                    <p>Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem.</p>
                  </div>
                </div>

                <div class="col">
                  <div class="value-card">
                    <div class="value-icon">
                      <i class="bi bi-globe"></i>
                    </div>
                    <h4>Global Perspective</h4>
                    <p>Donec sollicitudin molestie malesuada. Curabitur non nulla sit amet nisl tempus.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div> -->

      </div>

    </section><!-- /About Section -->

    <!-- Students Life Block Section -->
    <section id="students-life-block" class="students-life-block custom-section section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2 class="mb-0 text-white">Research</h2>
        <!-- <p>What does research say about "My Place"?</p> -->
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center g-5 mb-5">
          <div class="col-lg-5" data-aos="fade-right" data-aos-delay="200">
            <div class="hero-image-wrapper">
              <img src="assets/img/kids-studying.png" alt="Student Life" class="img-fluid main-image">
              <div class="floating-card" data-aos="zoom-in" data-aos-delay="400">
                <div class="card-icon">
                  <i class="bi bi-people-fill"></i>
                </div>
                <div class="card-content">
                  <span class="card-number">2500+</span>
                  <span class="card-label">Active Students</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-7" data-aos="fade-left" data-aos-delay="300">
            <div class="content-wrapper">
              <h2 data-aos="fade-up" data-aos-delay="400" class="text-white">What does research say about "My Place"?</h2>
              <p class="lead-text text-white mb-2" data-aos="fade-up" data-aos-delay="450">There's emerging research suggesting “values affirmation intervention and social belonging interventions markedly improve academic performance and health of stigmatized racial groups.”</p>

              <p class="lead-text text-white mb-2" data-aos="fade-up" data-aos-delay="450">This is summarized in a 2019 Harvard article by Williams, Lawrence, and Davis. It concludes self-affirmation enhances coping with stress, improves health behaviour, and boosts academic success.</p>
              
              <p class="lead-text text-white mb-2" data-aos="fade-up" data-aos-delay="450">My Place in This World curriculum aligns with these findings and aims to bring positive life outcomes, especially for BIPOC students.</p>

              <!-- <div class="info-grid" data-aos="fade-up" data-aos-delay="500">
                <div class="info-item">
                  <div class="info-icon">
                    <i class="bi bi-calendar-event"></i>
                  </div>
                  <div class="info-text">
                    <strong>Year-Round Events</strong>
                    <span>Interactive programming throughout the year reinforces belonging and personal growth, helping students stay engaged and confident.</span>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="bi bi-award"></i>
                  </div>
                  <div class="info-text">
                    <strong>Achievement Programs</strong>
                    <span>Custom-built activities promote self-affirmation, goal setting, and academic success — especially tailored for BIPOC youth.</span>
                  </div>
                </div>
              </div> -->

              <div class="cta-section" data-aos="fade-up" data-aos-delay="600">
                <a href="student-activities.html" class="btn-primary cta-warning">Learn More</a>
              </div>
            </div>
          </div>
        </div>

        <!-- <div class="activities-showcase">
          <div class="row g-4">
            <div class="col-lg-8" data-aos="fade-right" data-aos-delay="200">
              <div class="featured-activity">
                <div class="activity-media">
                  <img src="assets/img/education/activities-2.webp" alt="Featured Activity" class="img-fluid">
                  <div class="activity-overlay">
                    <div class="overlay-content">
                      <h4>Student Organizations</h4>
                      <p>Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo.</p>
                      <a href="#" class="overlay-btn">
                        <i class="bi bi-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4" data-aos="fade-left" data-aos-delay="300">
              <div class="activities-list">
                <div class="activity-item" data-aos="slide-up" data-aos-delay="350">
                  <div class="activity-thumb">
                    <img src="assets/img/education/activities-6.webp" alt="Research Projects" class="img-fluid">
                  </div>
                  <div class="activity-info">
                    <h6>Research Projects</h6>
                    <p>Sed ut perspiciatis unde omnis natus error</p>
                  </div>
                </div>

                <div class="activity-item" data-aos="slide-up" data-aos-delay="400">
                  <div class="activity-thumb">
                    <img src="assets/img/education/activities-1.webp" alt="Community Service" class="img-fluid">
                  </div>
                  <div class="activity-info">
                    <h6>Community Service</h6>
                    <p>At vero eos et accusamus et iusto odio</p>
                  </div>
                </div>

                <div class="activity-item" data-aos="slide-up" data-aos-delay="450">
                  <div class="activity-thumb">
                    <img src="assets/img/education/activities-4.webp" alt="Innovation Labs" class="img-fluid">
                  </div>
                  <div class="activity-info">
                    <h6>Innovation Labs</h6>
                    <p>Temporibus autem quibusdam officiis debitis</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div> -->

      </div>

    </section><!-- /Students Life Block Section -->

    <section class="pt-0">

    <section id="leadership" class="leadership section division-of-learning py-4">

      <div class="dol-texture mb-5">
        <div class="dol-textured-img1"></div>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="leadership-section aos-init aos-animate" data-aos="fade-up">
          <div class="section-header text-center">
            <h2 class="title">Division Of Learning</h2>
          </div>

          <div class="dol-card-block">
            
              <div class="team-card primary-card">
                <div class="card-inner">
                  <div class="card-front">
                    <div class="member-image">
                      <img src="assets/img/person/person-m-4.webp" alt="Principal" class="img-fluid">
                    </div>
                    <div class="member-info">
                      <h4>Primary</h4>
                      <p>Grade(1-3)</p>
                    </div>
                  </div>
                  <div class="card-back">
                    <ul>
                      <li>Identify Africa as a continent of many countries</li>
                      <li>Learn some important facts that contribute to it being a “majestic” place in this world</li>
                      <li>Explore what it means to be an African King or Queen before transatlantic slavery</li>
                    </ul>
                    <a class="learn-link" href="/membership/">
                      <button class="btn btn-dark">Learn More</button>
                    </a>
                  </div>
                </div>
              
              </div>

              <div class="team-card junior-card">
                <div class="card-inner">
                  <div class="card-front">
                    <div class="member-image">
                      <img src="assets/img/person/person-f-6.webp" alt="Vice Principal" class="img-fluid">
                    </div>
                    <div class="member-info">
                      <h4>Junior</h4>
                      <p>Grade(4-6)</p>
                    </div>
                  </div>
                  <div class="card-back">
                    <ul>
                      <li>Identify the positive attributes of African experiences while debunking negative stereotypes</li>
                      <li>Explore the influence and contributions of African royalty before transatlantic slavery and up to the present</li>
                      <li>Make connections to their own experiences and current environment</li>
                    </ul>
                    <a class="learn-link" href="/membership/">
                      <button class="btn btn-dark">Learn More</button>
                    </a>
                  </div>
                </div>
              </div>
            
              <div class="team-card high-school-card">
                <div class="card-inner">
                  <div class="card-front">
                    <div class="member-image">
                      <img src="assets/img/person/person-f-4.webp" alt="Counseling Head" class="img-fluid">
                    </div>
                    <div class="member-info">
                      <h4>High School</h4>
                      <p>Grade(9-12)</p>
                    </div>
                  </div>
                  <div class="card-back">
                    <ul>
                      <li>Identify the positive attributes of African experiences while debunking negative stereotypes</li>
                      <li>Explore the influence and contributions of African royalty before transatlantic slavery and up to the present</li>
                      <li>Develop subject-specific curricular skills as they make connections to their lives, current events, and present-day Black leaders</li>
                    </ul>
                    <a class="learn-link" href="/membership/">
                      <button class="btn btn-dark">Learn More</button>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="dol-texture mt-5">
        <div class="dol-textured-img2"></div>
      </div>

    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Testimonials</h2>
        <p>Our team believes that the best advocacy comes from the people who will benefit from using this curriculum. Here's an opportunity to listen to what the key stakeholders are saying.</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="testimonial-slider swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 4000
              },
              "slidesPerView": 1,
              "spaceBetween": 30,
              "navigation": {
                "nextEl": ".swiper-button-next",
                "prevEl": ".swiper-button-prev"
              },
              "breakpoints": {
                "768": {
                  "slidesPerView": 2
                },
                "1200": {
                  "slidesPerView": 3
                }
              }
            }
          </script>

          <div class="swiper-wrapper">

            <!-- Testimonial Slide 1 -->
            <div class="swiper-slide">
              <div class="testimonial-item" data-aos="zoom-in" data-aos-delay="200">
                <div class="testimonial-header">
                  <img src="assets/img/person/person-f-12.webp" alt="Client" class="img-fluid" loading="lazy">
                </div>
                <div class="testimonial-body">
                  <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum consectetur adipiscing elit sed eiusmod tempor.</p>
                </div>
                <div class="testimonial-footer">
                  <h5>Jessica Martinez</h5>
                  <span>UX Designer</span>
                </div>
              </div>
            </div><!-- End Testimonial Slide -->

            <!-- Testimonial Slide 2 -->
            <div class="swiper-slide">
              <div class="testimonial-item" data-aos="zoom-in" data-aos-delay="300">
                <div class="testimonial-header">
                  <img src="assets/img/person/person-m-8.webp" alt="Client" class="img-fluid" loading="lazy">
                </div>
                <div class="testimonial-body">
                  <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa.</p>
                </div>
                <div class="testimonial-footer">
                  <h5>David Rodriguez</h5>
                  <span>Software Engineer</span>
                </div>
              </div>
            </div><!-- End Testimonial Slide -->

            <!-- Testimonial Slide 3 -->
            <div class="swiper-slide">
              <div class="testimonial-item" data-aos="zoom-in" data-aos-delay="400">
                <div class="testimonial-header">
                  <img src="assets/img/person/person-f-6.webp" alt="Client" class="img-fluid" loading="lazy">
                </div>
                <div class="testimonial-body">
                  <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud.</p>
                </div>
                <div class="testimonial-footer">
                  <h5>Amanda Wilson</h5>
                  <span>Creative Director</span>
                </div>
              </div>
            </div><!-- End Testimonial Slide -->

            <!-- Testimonial Slide 4 -->
            <div class="swiper-slide">
              <div class="testimonial-item" data-aos="zoom-in" data-aos-delay="500">
                <div class="testimonial-header">
                  <img src="assets/img/person/person-m-12.webp" alt="Client" class="img-fluid" loading="lazy">
                </div>
                <div class="testimonial-body">
                  <p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium totam rem aperiam eaque ipsa quae ab illo inventore veritatis.</p>
                </div>
                <div class="testimonial-footer">
                  <h5>Ryan Thompson</h5>
                  <span>Business Analyst</span>
                </div>
              </div>
            </div><!-- End Testimonial Slide -->

            <!-- Testimonial Slide 5 -->
            <div class="swiper-slide">
              <div class="testimonial-item" data-aos="zoom-in" data-aos-delay="600">
                <div class="testimonial-header">
                  <img src="assets/img/person/person-f-10.webp" alt="Client" class="img-fluid" loading="lazy">
                </div>
                <div class="testimonial-body">
                  <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi.</p>
                </div>
                <div class="testimonial-footer">
                  <h5>Rachel Chen</h5>
                  <span>Project Manager</span>
                </div>
              </div>
            </div><!-- End Testimonial Slide -->

          </div>

          <div class="swiper-navigation">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
          </div>

        </div>

      </div>

    </section><!-- /Testimonials Section -->

    <!-- Stats Section -->
    <section id="stats" class="stats section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
          <div class="col-lg-8 text-center">
            <div class="intro-content" data-aos="fade-up" data-aos-delay="200">
              <h2 class="section-heading">Transforming Lives Through Quality Education</h2>
              <p class="section-description">Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
            </div>
          </div>
        </div>

        <div class="row g-4 mt-4">
          <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="metric-card" data-aos="flip-left" data-aos-delay="300">
              <div class="metric-header">
                <div class="metric-icon-wrapper">
                  <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="metric-value">
                  <span data-purecounter-start="0" data-purecounter-end="87" data-purecounter-duration="1" class="purecounter"></span>%
                </div>
              </div>
              <div class="metric-info">
                <h4>Success Rate</h4>
                <p>Alumni employment within 6 months</p>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="metric-card" data-aos="flip-left" data-aos-delay="400">
              <div class="metric-header">
                <div class="metric-icon-wrapper">
                  <i class="bi bi-building"></i>
                </div>
                <div class="metric-value">
                  <span data-purecounter-start="0" data-purecounter-end="8" data-purecounter-duration="1" class="purecounter"></span>
                </div>
              </div>
              <div class="metric-info">
                <h4>Campus Locations</h4>
                <p>Across the country serving students</p>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="metric-card" data-aos="flip-left" data-aos-delay="500">
              <div class="metric-header">
                <div class="metric-icon-wrapper">
                  <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="metric-value">
                  <span data-purecounter-start="0" data-purecounter-end="250" data-purecounter-duration="1" class="purecounter"></span>+
                </div>
              </div>
              <div class="metric-info">
                <h4>Awards Received</h4>
                <p>Recognition for educational excellence</p>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="metric-card" data-aos="flip-left" data-aos-delay="600">
              <div class="metric-header">
                <div class="metric-icon-wrapper">
                  <i class="bi bi-globe"></i>
                </div>
                <div class="metric-value">
                  <span data-purecounter-start="0" data-purecounter-end="65" data-purecounter-duration="1" class="purecounter"></span>+
                </div>
              </div>
              <div class="metric-info">
                <h4>Countries Represented</h4>
                <p>Diverse international student body</p>
              </div>
            </div>
          </div>
        </div>

        <div class="row mt-5">
          <div class="col-lg-12">
            <div class="highlights-section" data-aos="fade-up" data-aos-delay="700">
              <div class="row align-items-center">
                <div class="col-lg-6">
                  <div class="highlights-content">
                    <h3 class="highlights-title">Building Tomorrow's Leaders Today</h3>
                    <p class="highlights-text">Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim.</p>
                    <div class="highlights-features">
                      <div class="feature-item" data-aos="fade-right" data-aos-delay="800">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Innovative curriculum design</span>
                      </div>
                      <div class="feature-item" data-aos="fade-right" data-aos-delay="900">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>World-class faculty expertise</span>
                      </div>
                      <div class="feature-item" data-aos="fade-right" data-aos-delay="1000">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Comprehensive student support</span>
                      </div>
                    </div>
                    <div class="highlights-cta">
                      <a href="#" class="cta-btn primary">Explore Programs</a>
                      <a href="#" class="cta-btn secondary">Download Brochure</a>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="highlights-gallery">
                    <div class="gallery-grid">
                      <div class="gallery-item large" data-aos="zoom-in" data-aos-delay="800">
                        <img src="assets/img/education/campus-3.webp" alt="Campus Life" class="img-fluid" loading="lazy">
                        <div class="gallery-overlay">
                          <h5>Modern Campus</h5>
                        </div>
                      </div>
                      <div class="gallery-item small" data-aos="zoom-in" data-aos-delay="900">
                        <img src="assets/img/education/students-5.webp" alt="Students" class="img-fluid" loading="lazy">
                        <div class="gallery-overlay">
                          <h6>Student Life</h6>
                        </div>
                      </div>
                      <div class="gallery-item small" data-aos="zoom-in" data-aos-delay="1000">
                        <img src="assets/img/education/teacher-7.webp" alt="Faculty" class="img-fluid" loading="lazy">
                        <div class="gallery-overlay">
                          <h6>Expert Faculty</h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Stats Section -->

    <!-- Recent News Section -->
    <section id="recent-news" class="recent-news section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Recent News</h2>
        <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <div class="col-xl-6" data-aos="fade-up" data-aos-delay="100">
            <article class="post-item d-flex">
              <div class="post-img">
                <img src="assets/img/blog/blog-post-1.webp" alt="" class="img-fluid" loading="lazy">
              </div>

              <div class="post-content flex-grow-1">
                <a href="#" class="category">Design</a>

                <h2 class="post-title">
                  <a href="#">Sed ut perspiciatis unde omnis</a>
                </h2>

                <p class="post-description">
                  Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores.
                </p>

                <div class="post-meta">
                  <div class="post-author">
                    <img src="assets/img/person/person-f-12.webp" alt="" class="img-fluid">
                    <span class="author-name">Lina Chen</span>
                  </div>
                  <span class="post-date">Mar 15, 2025</span>
                </div>
              </div>
            </article>
          </div><!-- End post item -->

          <div class="col-xl-6" data-aos="fade-up" data-aos-delay="200">
            <article class="post-item d-flex">
              <div class="post-img">
                <img src="assets/img/blog/blog-post-2.webp" alt="" class="img-fluid" loading="lazy">
              </div>

              <div class="post-content flex-grow-1">
                <a href="#" class="category">Product</a>

                <h2 class="post-title">
                  <a href="#">At vero eos et accusamus</a>
                </h2>

                <p class="post-description">
                  Et harum quidem rerum facilis est et expedita distinctio nam libero tempore, cum soluta nobis est eligendi.
                </p>

                <div class="post-meta">
                  <div class="post-author">
                    <img src="assets/img/person/person-f-13.webp" alt="" class="img-fluid">
                    <span class="author-name">Sofia Rodriguez</span>
                  </div>
                  <span class="post-date">Apr 22, 2025</span>
                </div>
              </div>
            </article>
          </div><!-- End post item -->

          <div class="col-xl-6" data-aos="fade-up" data-aos-delay="300">
            <article class="post-item d-flex">
              <div class="post-img">
                <img src="assets/img/blog/blog-post-3.webp" alt="" class="img-fluid" loading="lazy">
              </div>

              <div class="post-content flex-grow-1">
                <a href="#" class="category">Software Engineering</a>

                <h2 class="post-title">
                  <a href="#">Temporibus autem quibusdam</a>
                </h2>

                <p class="post-description">
                  Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur.
                </p>

                <div class="post-meta">
                  <div class="post-author">
                    <img src="assets/img/person/person-m-10.webp" alt="" class="img-fluid">
                    <span class="author-name">Lucas Thompson</span>
                  </div>
                  <span class="post-date">May 8, 2025</span>
                </div>
              </div>
            </article>
          </div><!-- End post item -->

          <div class="col-xl-6" data-aos="fade-up" data-aos-delay="400">
            <article class="post-item d-flex">
              <div class="post-img">
                <img src="assets/img/blog/blog-post-4.webp" alt="" class="img-fluid" loading="lazy">
              </div>

              <div class="post-content flex-grow-1">
                <a href="#" class="category">Creative</a>

                <h2 class="post-title">
                  <a href="#">Nam libero tempore soluta</a>
                </h2>

                <p class="post-description">
                  Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                </p>

                <div class="post-meta">
                  <div class="post-author">
                    <img src="assets/img/person/person-f-14.webp" alt="" class="img-fluid">
                    <span class="author-name">Emma Patel</span>
                  </div>
                  <span class="post-date">Jun 30, 2025</span>
                </div>
              </div>
            </article>
          </div><!-- End post item -->

        </div>

      </div>

    </section><!-- /Recent News Section -->

    <!-- Events Section -->
    <section id="events" class="events section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Events</h2>
        <p>Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-4">

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="event-item">
              <div class="event-image">
                <img src="assets/img/education/events-3.webp" alt="Workshop" class="img-fluid">
                <div class="event-date-overlay">
                  <span class="date">MAR<br>18</span>
                </div>
              </div>
              <div class="event-details">
                <div class="event-category">
                  <span class="badge academic">Academic</span>
                  <span class="event-time">2:00 PM</span>
                </div>
                <h3>Advanced Mathematics Workshop</h3>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                <div class="event-info">
                  <div class="info-row">
                    <i class="bi bi-geo-alt"></i>
                    <span>Room 205, Science Building</span>
                  </div>
                  <div class="info-row">
                    <i class="bi bi-people"></i>
                    <span>25 Participants</span>
                  </div>
                </div>
                <div class="event-footer">
                  <a href="#" class="register-btn">Register Now</a>
                  <div class="event-share">
                    <i class="bi bi-share"></i>
                    <i class="bi bi-heart"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
            <div class="event-item">
              <div class="event-image">
                <img src="assets/img/education/events-5.webp" alt="Tournament" class="img-fluid">
                <div class="event-date-overlay">
                  <span class="date">APR<br>05</span>
                </div>
              </div>
              <div class="event-details">
                <div class="event-category">
                  <span class="badge sports">Sports</span>
                  <span class="event-time">9:00 AM</span>
                </div>
                <h3>Inter-School Basketball Championship</h3>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed eiusmod tempor incididunt ut labore et dolore magna.</p>
                <div class="event-info">
                  <div class="info-row">
                    <i class="bi bi-geo-alt"></i>
                    <span>Sports Complex Gym</span>
                  </div>
                  <div class="info-row">
                    <i class="bi bi-people"></i>
                    <span>8 Teams</span>
                  </div>
                </div>
                <div class="event-footer">
                  <a href="#" class="register-btn">Register Now</a>
                  <div class="event-share">
                    <i class="bi bi-share"></i>
                    <i class="bi bi-heart"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="400">
            <div class="event-item">
              <div class="event-image">
                <img src="assets/img/education/events-7.webp" alt="Art Exhibition" class="img-fluid">
                <div class="event-date-overlay">
                  <span class="date">APR<br>12</span>
                </div>
              </div>
              <div class="event-details">
                <div class="event-category">
                  <span class="badge arts">Arts</span>
                  <span class="event-time">6:00 PM</span>
                </div>
                <h3>Student Art Exhibition Opening</h3>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore.</p>
                <div class="event-info">
                  <div class="info-row">
                    <i class="bi bi-geo-alt"></i>
                    <span>Art Gallery, First Floor</span>
                  </div>
                  <div class="info-row">
                    <i class="bi bi-people"></i>
                    <span>Open to All</span>
                  </div>
                </div>
                <div class="event-footer">
                  <a href="#" class="register-btn">Register Now</a>
                  <div class="event-share">
                    <i class="bi bi-share"></i>
                    <i class="bi bi-heart"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="event-item">
              <div class="event-image">
                <img src="assets/img/education/events-2.webp" alt="Science Fair" class="img-fluid">
                <div class="event-date-overlay">
                  <span class="date">MAY<br>03</span>
                </div>
              </div>
              <div class="event-details">
                <div class="event-category">
                  <span class="badge academic">Academic</span>
                  <span class="event-time">10:00 AM</span>
                </div>
                <h3>Annual Science Fair Competition</h3>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                <div class="event-info">
                  <div class="info-row">
                    <i class="bi bi-geo-alt"></i>
                    <span>Main Auditorium Hall</span>
                  </div>
                  <div class="info-row">
                    <i class="bi bi-people"></i>
                    <span>45 Projects</span>
                  </div>
                </div>
                <div class="event-footer">
                  <a href="#" class="register-btn">Register Now</a>
                  <div class="event-share">
                    <i class="bi bi-share"></i>
                    <i class="bi bi-heart"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="300">
            <div class="event-item">
              <div class="event-image">
                <img src="assets/img/education/events-8.webp" alt="Community Event" class="img-fluid">
                <div class="event-date-overlay">
                  <span class="date">MAY<br>15</span>
                </div>
              </div>
              <div class="event-details">
                <div class="event-category">
                  <span class="badge community">Community</span>
                  <span class="event-time">3:00 PM</span>
                </div>
                <h3>Family Fun Day Celebration</h3>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore.</p>
                <div class="event-info">
                  <div class="info-row">
                    <i class="bi bi-geo-alt"></i>
                    <span>School Playground Area</span>
                  </div>
                  <div class="info-row">
                    <i class="bi bi-people"></i>
                    <span>All Families</span>
                  </div>
                </div>
                <div class="event-footer">
                  <a href="#" class="register-btn">Register Now</a>
                  <div class="event-share">
                    <i class="bi bi-share"></i>
                    <i class="bi bi-heart"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="400">
            <div class="event-item">
              <div class="event-image">
                <img src="assets/img/education/events-6.webp" alt="Music Concert" class="img-fluid">
                <div class="event-date-overlay">
                  <span class="date">JUN<br>02</span>
                </div>
              </div>
              <div class="event-details">
                <div class="event-category">
                  <span class="badge arts">Arts</span>
                  <span class="event-time">7:30 PM</span>
                </div>
                <h3>Summer Music Concert Finale</h3>
                <p>Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna.</p>
                <div class="event-info">
                  <div class="info-row">
                    <i class="bi bi-geo-alt"></i>
                    <span>Music Hall Theater</span>
                  </div>
                  <div class="info-row">
                    <i class="bi bi-people"></i>
                    <span>300 Seats</span>
                  </div>
                </div>
                <div class="event-footer">
                  <a href="#" class="register-btn">Register Now</a>
                  <div class="event-share">
                    <i class="bi bi-share"></i>
                    <i class="bi bi-heart"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="events-navigation" data-aos="fade-up" data-aos-delay="500">
          <div class="row align-items-center">
            <div class="col-md-8">
              <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All Events</button>
                <button class="filter-tab" data-filter="academic">Academic</button>
                <button class="filter-tab" data-filter="sports">Sports</button>
                <button class="filter-tab" data-filter="arts">Arts</button>
                <button class="filter-tab" data-filter="community">Community</button>
              </div>
            </div>
            <div class="col-md-4 text-end">
              <a href="#" class="view-calendar-btn">
                <i class="bi bi-calendar3"></i>
                View Calendar
              </a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Events Section -->

  </main>

@endsection
