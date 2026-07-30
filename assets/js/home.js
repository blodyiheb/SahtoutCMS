 // Tab Functionality
        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        const youtubeSettings = window.homeYoutubeSettings || {
            embedUrl: 'https://www.youtube.com/embed/DjuN1dE50VI?rel=0&modestbranding=1',
            title: 'Sahtout Server Trailer',
            description: 'Lichking Trailer, Replace it with your own ....'
        };

        document.querySelectorAll('.tab').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                const tab = button.getAttribute('data-tab');
                const contentBox = document.getElementById('tab-content');
                if (tab === 'youtube') {
                    contentBox.innerHTML = `
                        <div class="tab-panel tab-panel--video">
                            <div class="tab-kicker">YouTube</div>
                            <h2>${escapeHtml(youtubeSettings.title)}</h2>
                            <p>${escapeHtml(youtubeSettings.description)}</p>
                            <div class="video-card">
                                <div class="video-frame">
                                    <iframe src="${escapeHtml(youtubeSettings.embedUrl)}"
                                            title="${escapeHtml(youtubeSettings.title)}"
                                            loading="lazy"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (tab === 'news') {
                    contentBox.innerHTML = `
                        <h2>Server News</h2>
                        <p>Our server just launched! 🎉 Join now and explore the realms.</p>
                        <p>Patch notes, updates, and events will appear here regularly.</p>
                    `;
                } else if (tab === 'bugtracker') {
                    contentBox.innerHTML = `
                        <h2>Bug Tracker</h2>
                        <p>Found a bug? Report it via our Discord or GitHub bug tracker.</p>
                        <p><a href="https://github.com/YourServer/bugtracker" target="_blank">Go to Bugtracker</a></p>
                    `;
                }
            });
        });

        // Slider Functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.style.transform = `translateX(${-index * 100}%)`;
                dots[i].classList.toggle('active', i === index);
            });
            currentSlide = index;
        }

        document.querySelector('.slider-nav.prev').addEventListener('click', () => {
            showSlide((currentSlide - 1 + totalSlides) % totalSlides);
        });

        document.querySelector('.slider-nav.next').addEventListener('click', () => {
            showSlide((currentSlide + 1) % totalSlides);
        });

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                showSlide(parseInt(dot.getAttribute('data-slide')));
            });
        });

        // Auto-slide every 5 seconds
        setInterval(() => {
            showSlide((currentSlide + 1) % totalSlides);
        }, 5000);