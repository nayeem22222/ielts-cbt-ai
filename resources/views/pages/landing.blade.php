<x-layouts.app title="Arif Academy IELTS CBT & LMS">
<div class="aa-home-pro">
    <div class="home-wrap">
        <nav class="topbar" aria-label="Main navigation">
            <button class="hamburger" aria-label="Open menu"><span></span><span></span><span></span></button>
            <form class="search-box" role="search">
                <input type="search" placeholder="Search for courses, tests, videos..." aria-label="Search courses tests videos">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.3-4.3m1.3-5.2a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
            </form>
            <div class="top-actions">
                <a href="/courses" class="refer-link"><span class="gift-icon"></span><span><b>Refer & Earn</b><small>Earn Rewards</small></span></a>
                <a href="/login" class="notif" aria-label="Notifications"><span></span><em>3</em></a>
                <a href="/login" class="student-menu"><i></i><strong>Hello, Student!</strong><svg viewBox="0 0 20 20"><path d="m5 7 5 5 5-5"/></svg></a>
            </div>
        </nav>

        <section class="hero-pro">
            <div class="hero-content">
                <h1>Your Success in<br><span>IELTS Starts</span><br>with the Right Plan</h1>
                <p>Access unlimited practice, expert guidance, and proven strategies to achieve your target band score.</p>
                <div class="hero-cta">
                    <a href="/register" class="orange-btn">Start Practicing <svg viewBox="0 0 20 20"><path d="M4 10h12m-5-5 5 5-5 5"/></svg></a>
                    <a href="/courses" class="green-outline-btn">Explore Courses</a>
                </div>
                <div class="learners-row">
                    <div class="learner-avatars"><span></span><span></span><span></span></div>
                    <div><b>50,000+ Students</b><small>are already learning with us</small></div>
                </div>
            </div>
            <div class="hero-person">
                <img src="{{ asset('images/landing/student-hero.svg') }}" alt="IELTS student illustration">
            </div>
            <aside class="score-panel" aria-label="Target band score">
                <h3>Target Band Score</h3>
                <div class="score-ring"><b>7.5+</b></div>
                <p>You can do it!</p>
                <svg class="mini-chart" viewBox="0 0 190 55" aria-hidden="true"><path d="M2 46 20 35l18 6 18-18 18 8 18-15 18 7 18-17 18 6 20-20"/><path d="m166 12 20-8-8 20"/></svg>
                <a href="/exam/reading" class="green-outline-btn">Take a Free Test <svg viewBox="0 0 20 20"><path d="M4 10h12m-5-5 5 5-5 5"/></svg></a>
            </aside>
        </section>

        <section class="stats-pro" aria-label="Academy statistics">
            <div><i class="icon-users"></i><b>50,000+</b><span>Happy Students</span></div>
            <div><i class="icon-cap orange"></i><b>1,200+</b><span>Courses</span></div>
            <div><i class="icon-test"></i><b>10,000+</b><span>Practice Tests</span></div>
            <div><i class="icon-play orange"></i><b>5,000+</b><span>Video Lessons</span></div>
            <div><i class="icon-medal"></i><b>99%</b><span>Success Rate</span></div>
        </section>

        <section class="offer-section">
            <div class="offer-head"><h2><span></span>Explore What We Offer</h2><a href="/courses">View All <svg viewBox="0 0 20 20"><path d="M4 10h12m-5-5 5 5-5 5"/></svg></a></div>
            <div class="offer-pro-grid">
                <a href="/courses" class="offer-pro"><i class="icon-test big"></i><h3>Unlimited Tests</h3><p>Access unlimited tests and track progress</p><em>→</em></a>
                <a href="/courses" class="offer-pro orange-card"><i class="icon-laptop big"></i><h3>Expert Videos</h3><p>Watch high-quality video lessons anytime</p><em>→</em></a>
                <a href="/courses" class="offer-pro"><i class="icon-live big"></i><h3>Live Classes</h3><p>Join interactive live classes with experts</p><em>→</em></a>
                <a href="/courses" class="offer-pro orange-card"><i class="icon-book big"></i><h3>Study Materials</h3><p>Download PDFs, eBooks and useful resources</p><em>→</em></a>
                <a href="/courses" class="offer-pro"><i class="icon-chart big"></i><h3>Track Progress</h3><p>Analyze performance and improve</p><em>→</em></a>
                <a href="/courses" class="offer-pro orange-card"><i class="icon-mic big"></i><h3>Speaking Practice</h3><p>Improve speaking with mock tests</p><em>→</em></a>
            </div>
        </section>

        <section class="roadmap-pro">
            <div class="road-books" aria-hidden="true"><i></i><i></i><i></i></div>
            <div class="road-text"><h2>Not Sure Where to Start?</h2><p>Follow our 7+ Study Roadmap and plan your IELTS preparation the smart way.</p></div>
            <a href="/courses" class="orange-btn">View 7+ Study Roadmap <svg viewBox="0 0 20 20"><path d="M4 10h12m-5-5 5 5-5 5"/></svg></a>
            <div class="road-path" aria-hidden="true"></div>
        </section>
    </div>
</div>
</x-layouts.app>
