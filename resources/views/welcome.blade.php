<x-app-layout>
    <x-slot name="title">{{ __('Learn together, anywhere') }}</x-slot>
    <x-slot name="description">{{ __('EduConnect brings live video classes, a friendly social feed with posts and hashtags, and one-click class enrolment together for teachers and students.') }}</x-slot>

    <div class="edu-landing">
        {{-- ------------------------------------------------------------------
             Hero
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-hero" aria-labelledby="hero-title">
            <span class="edu-landing-shape is-one" aria-hidden="true"></span>
            <span class="edu-landing-shape is-two" aria-hidden="true"></span>

            <div class="container-xxl px-3 px-lg-4 position-relative">
                <div class="row align-items-center g-5">
                    <div class="col-12 col-lg-6">
                        <span class="edu-eyebrow mb-3">
                            <i class="bi bi-stars" aria-hidden="true"></i>
                            {{ __('A calmer way to teach online') }}
                        </span>

                        <h1 class="edu-landing-title fw-bold mb-3" id="hero-title">
                            {{ __('Teach and learn,') }}
                            <span class="text-edu">{{ __('together') }}</span>.
                        </h1>

                        <p class="lead text-muted mb-4 edu-measure">
                            {{ __('Live video classes, a friendly social feed and simple class management — all in one place. Students follow their teachers and enroll in a class in a single click.') }}
                        </p>

                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 mb-3">
                            <a href="{{ route('register', ['role' => 'student']) }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-mortarboard me-1" aria-hidden="true"></i>
                                {{ __('Register as Student') }}
                            </a>
                            <a href="{{ route('register', ['role' => 'teacher']) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-easel me-1" aria-hidden="true"></i>
                                {{ __('Register as Teacher') }}
                            </a>

                        </div>

                        <p class="small text-muted mb-0">
                            <i class="bi bi-check2-circle text-success me-1" aria-hidden="true"></i>
                            {{ __('Free to start. No credit card, no download.') }}
                        </p>
                    </div>

                    <div class="col-12 col-lg-6">
                        {{-- CSS-only mockup of a live class: no image request, no layout shift. --}}
                        <div class="edu-mockup" role="img"
                             aria-label="{{ __('Preview of a live class: teacher video, shared notes and participant tiles.') }}">
                            <div class="edu-mockup-bar" aria-hidden="true">
                                <span class="edu-mockup-dot"></span>
                                <span class="edu-mockup-dot"></span>
                                <span class="edu-mockup-dot"></span>
                                <span class="ms-auto small text-muted fw-semibold">{{ __('Algebra basics · Live') }}</span>
                            </div>

                            <div class="edu-mockup-stage">
                                <span class="edu-mockup-tile" style="left: 6%; top: 8%; width: 58%; height: 58%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="right: 6%; top: 8%; width: 28%; height: 26%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="right: 6%; bottom: 8%; width: 28%; height: 26%;" aria-hidden="true"></span>
                                <span class="edu-mockup-tile" style="left: 6%; bottom: 8%; width: 58%; height: 20%;" aria-hidden="true"></span>

                                <span class="position-absolute top-0 start-0 m-3 badge text-bg-danger">
                                    <i class="bi bi-broadcast me-1" aria-hidden="true"></i>{{ __('Live') }}
                                </span>
                            </div>

                            <div class="edu-mockup-bar-strip" aria-hidden="true">
                                <span class="edu-mockup-tool"><i class="bi bi-mic"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-camera-video"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-chat-dots"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-people"></i></span>
                                <span class="edu-mockup-tool"><i class="bi bi-telephone-x text-danger"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             How it works
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section" aria-labelledby="steps-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="text-center mb-5">
                    <span class="edu-eyebrow mb-2">{{ __('How it works') }}</span>
                    <h2 class="edu-section-title fw-bold mb-2" id="steps-title">{{ __('Three steps to your first class') }}</h2>
                    <p class="text-muted mb-0 mx-auto edu-measure">
                        {{ __('Whether you teach or learn, you only need a browser and a few minutes.') }}
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">1</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Create your account') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Pick Student or Teacher, add your name and you are in. Admins create staff accounts for you.') }}
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">2</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Find or create a class') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Teachers publish a class with a date and time. Students browse by subject and enroll in one click.') }}
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="edu-step-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="edu-step-number" aria-hidden="true">3</span>
                                <h3 class="h6 fw-bold mb-0">{{ __('Meet, chat and keep learning') }}</h3>
                            </div>
                            <p class="small text-muted mb-0">
                                {{ __('Join the live room, then keep the conversation going in the feed with posts, comments and hashtags.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Features
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="features-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="text-center mb-5">
                    <span class="edu-eyebrow mb-2">{{ __('Everything in one place') }}</span>
                    <h2 class="edu-section-title fw-bold mb-2" id="features-title">{{ __('Built for real classrooms') }}</h2>
                    <p class="text-muted mb-0 mx-auto edu-measure">
                        {{ __('No plugin maze. Each feature solves one daily teaching moment.') }}
                    </p>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="edu-feature-card h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <i class="bi bi-easel text-edu me-1" aria-hidden="true"></i>
                                {{ __('For teachers') }}
                            </h3>
                            <p class="small text-muted mb-0">
                                {{ __('Schedule a class, share a private Jitsi room, see who enrolled, and keep the conversation going in the feed.') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="edu-feature-card h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <i class="bi bi-mortarboard text-edu me-1" aria-hidden="true"></i>
                                {{ __('For students') }}
                            </h3>
                            <p class="small text-muted mb-0">
                                {{ __('Follow teachers, enrol in one click, join live rooms from the browser, and ask questions in posts and comments.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ([
                        ['bi-camera-video', __('Live classes'), __('Open a secure video room inside EduConnect and start teaching right away.')],
                        ['bi-chat-square-text', __('Social feed'), __('Short posts, images, likes and comments keep the class community close.')],
                        ['bi-hash', __('Hashtags'), __('Group posts by topic and discover the five busiest tags of the week.')],
                        ['bi-bell', __('Notifications'), __('Followers, likes, comments and starting classes arrive in one calm inbox.')],
                        ['bi-shield-check', __('Safe moderation'), __('Report content, block accounts and keep every learner protected.')],
                        ['bi-speedometer2', __('Role dashboards'), __('Students, teachers and admins each see exactly what they need.')],
                    ] as [$icon, $title, $copy])
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="edu-feature-card">
                                <span class="edu-auth-icon mb-3" aria-hidden="true">
                                    <i class="bi {{ $icon }}"></i>
                                </span>
                                <h3 class="h6 fw-bold">{{ $title }}</h3>
                                <p class="small text-muted mb-0">{{ $copy }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Stats strip
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="stats-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="edu-stats-strip">
                    <h2 class="visually-hidden" id="stats-title">{{ __('EduConnect at a glance') }}</h2>

                    <div class="row g-4 text-center text-md-start">
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">3</div>
                            <div class="small text-white-50">{{ __('role-based experiences') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">1</div>
                            <div class="small text-white-50">{{ __('click to enroll') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">0</div>
                            <div class="small text-white-50">{{ __('apps to install') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="edu-stat-figure">24/7</div>
                            <div class="small text-white-50">{{ __('access to your feed') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             Testimonial
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="quote-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <figure class="edu-step-card text-center mb-0">
                            <span class="edu-empty-illustration mx-auto" aria-hidden="true">
                                <i class="bi bi-quote"></i>
                            </span>
                            <blockquote class="mb-3">
                                <p class="h5 fw-semibold mb-0" id="quote-title">
                                    {{ __('“My students join from their phones, ask questions in the feed and never lose the thread between lessons.”') }}
                                </p>
                            </blockquote>
                            <figcaption class="small text-muted">
                                {{ __('A teacher using EduConnect every week') }}
                            </figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------
             FAQ
             ------------------------------------------------------------------ --}}
        <section class="edu-landing-section pt-0" aria-labelledby="faq-title">
            <div class="container-xxl px-3 px-lg-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <div class="text-center mb-4">
                            <span class="edu-eyebrow mb-2">{{ __('Questions') }}</span>
                            <h2 class="edu-section-title fw-bold mb-0" id="faq-title">{{ __('Frequently asked') }}</h2>
                        </div>

                        <div class="accordion edu-faq" id="landingFaq">
                            @foreach ([
                                [__('Do I need to install anything?'), __('No. EduConnect runs in your browser. The video room opens in the same tab and works on phones, tablets and computers.')],
                                [__('Is it free for students?'), __('Creating a Student account is free. Your school or teacher decides which classes are published.')],
                                [__('How do teachers start a class?'), __('A teacher schedules a class, opens the room when ready and presses “Start class”. Enrolled students then see the Join button.')],
                                [__('What happens if someone behaves badly?'), __('Any member can report a post or comment. Admins review reports, hide content and can block the account.')],
                                [__('Can I use it on a slow connection?'), __('Yes. Pages are server-rendered, images are lazy-loaded and the video room degrades gracefully with a clear retry message.')],
                            ] as $index => [$question, $answer])
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="faqHeading{{ $index }}">
                                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#faqCollapse{{ $index }}"
                                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                aria-controls="faqCollapse{{ $index }}">
                                            {{ $question }}
                                        </button>
                                    </h3>
                                    <div id="faqCollapse{{ $index }}"
                                         class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                         aria-labelledby="faqHeading{{ $index }}" data-bs-parent="#landingFaq">
                                        <div class="accordion-body small text-muted">{{ $answer }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
