
        // Tab Functionality
        document.querySelectorAll('.tab').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                const tab = button.getAttribute('data-tab');
                const contentBox = document.getElementById('tab-content');
                if (tab === 'news') {
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
                } else if (tab === 'stream') {
                    contentBox.innerHTML = `
                        <h2>Live Stream</h2>
                        <p>Watch our GMs or players live in action!</p>
                        <iframe src="https://player.twitch.tv/?channel=YourChannel&parent=yourdomain.com"
                                height="300" width="100%" allowfullscreen></iframe>
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
