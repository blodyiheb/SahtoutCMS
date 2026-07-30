                        document.getElementById('recaptcha_enabled').addEventListener('change', function() {
                            document.querySelector('.recaptcha-fields').classList.toggle('active', this.checked);
                        });