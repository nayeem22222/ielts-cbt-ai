<x-layouts.app title="Arif Academy IELTS CBT & LMS">
<div class="aa-home">
    <div class="home-shell">
        <nav class="home-top" aria-label="Main navigation">
            <button class="menu-btn" aria-label="Open menu">☰</button>
            <form class="home-search" role="search">
                <input type="search" placeholder="Search for courses, tests, videos..." aria-label="Search courses tests videos">
                <span aria-hidden="true">⌕</span>
            </form>
            <div class="home-actions">
                <a href="/courses" class="reward">
                    <span class="reward-icon">🎁</span>
                    <span><b>Refer & Earn</b><span>Earn Rewards</span></span>
                </a>
                <a href="/login" class="bell" aria-label="Notifications">🔔<span class="dot">3</span></a>
                <a href="/login" class="profile"><span class="avatar-img">👨‍🎓</span><span>Hello, Student!</span><span>⌄</span></a>
            </div>
        </nav>

        <section class="hero-card">
            <div class="hero-copy">
                <h1>Your Success in<br><span class="green">IELTS Starts</span><br>with the Right Plan</h1>
                <p>Access unlimited practice, expert guidance, and proven strategies to achieve your target band score.</p>
                <div class="hero-buttons">
                    <a class="btn-orange" href="/register">Start Practicing <span>→</span></a>
                    <a class="btn-outline-green" href="/courses">Explore Courses</a>
                </div>
                <div class="students-mini">
                    <div class="faces"><span>👨</span><span>👩</span><span>👨</span></div>
                    <div><strong>50,000+ Students</strong><small>are already learning with us</small></div>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <div class="student-art">
                    <div class="circle"></div>
                    <div class="student-card-avatar"></div>
                </div>
                <div class="score-card">
                    <h3>Target Band Score</h3>
                    <div class="ring"><span>7.5+</span></div>
                    <strong>You can do it!</strong>
                    <div class="spark">⌁⌁⌁⌁↗</div>
                    <a class="btn-outline-green" href="/exam/reading">Take a Free Test →</a>
                </div>
            </div>
        </section>

        <section class="stats-strip" aria-label="Academy statistics">
            @foreach([
                ['👥','50,000+','Happy Students'],
                ['🎓','1,200+','Courses'],
                ['📋','10,000+','Practice Tests'],
                ['▶','5,000+','Video Lessons'],
                ['🏅','99%','Success Rate'],
            ] as $stat)
                <div class="stat-item"><div class="stat-icon">{{ $stat[0] }}</div><div><b>{{ $stat[1] }}</b><span>{{ $stat[2] }}</span></div></div>
            @endforeach
        </section>

        <section>
            <div class="section-head">
                <h2>☘ Explore What We Offer</h2>
                <a href="/courses" class="view-all">View All →</a>
            </div>
            <div class="offer-grid">
                @foreach([
                    ['📋','Unlimited Tests','Access unlimited tests and track progress'],
                    ['💻','Expert Videos','Watch high-quality video lessons anytime'],
                    ['👨‍🏫','Live Classes','Join interactive live classes with experts'],
                    ['📖','Study Materials','Download PDFs, eBooks and useful resources'],
                    ['📈','Track Progress','Analyze performance and improve'],
                    ['🎙','Speaking Practice','Improve speaking with mock tests'],
                ] as $item)
                    <a href="/courses" class="offer-card">
                        <div class="offer-icon">{{ $item[0] }}</div>
                        <h3>{{ $item[1] }}</h3>
                        <p>{{ $item[2] }}</p>
                        <span class="go">→</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="roadmap">
            <div class="road-illustration">🎓📚</div>
            <div>
                <h2>Not Sure Where to Start?</h2>
                <p>Follow our 7+ Study Roadmap and plan your IELTS preparation the smart way.</p>
            </div>
            <a class="btn-orange" href="/courses">View 7+ Study Roadmap →</a>
            <div class="road-map">🛣️</div>
        </section>
    </div>
</div>
</x-layouts.app>
